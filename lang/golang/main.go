package main

import (
    "os"
    "database/sql"
    "encoding/json"
    "fmt"
    "log"
    "net/http"
    "strconv"
    "time"

    "github.com/golang-jwt/jwt/v5"
    "golang.org/x/crypto/bcrypt"
    "github.com/gorilla/mux"
    "github.com/joho/godotenv"
    _ "github.com/go-sql-driver/mysql"
)

var jwtKey []byte
var db *sql.DB

type User struct {
    ID    int    `json:"id"`
    Email string `json:"email"`
    Verified bool `json:"is_verified"`
    Created string `json:"created_at"`
    Roles string `json:"roles"`
}

type Credentials struct {
    Email string `json:"email"`
    Password string `json:"password"`
}

type Claims struct {
    Email string `json:"email"`
    jwt.RegisteredClaims
}

func mustGetenv(k string) string {
	v := os.Getenv(k)
	if v == "" {
		log.Fatalf("The %s variable is not set", k)
	}
	return v
}

func main() {
    var err error
	err = godotenv.Load()
	if err != nil {
		log.Fatal("Error loading .env file")
	}

	jwtKey = []byte(mustGetenv("JWT_SECRET_KEY"))
	dsn := mustGetenv("DATABASE_URL_API")
	appPort := mustGetenv("APP_PORT")
	addr := fmt.Sprintf(":%s", appPort)

    db, err = sql.Open("mysql", dsn)
    if err != nil {
        log.Fatal(err)
    }
    defer db.Close()

    r := mux.NewRouter()

    r.HandleFunc("/login", login).Methods("POST")
    r.HandleFunc("/users", authenticate(getUsers)).Methods("GET")
    r.HandleFunc("/users/{id}", authenticate(getUserByID)).Methods("GET")

	log.Printf("Server started on http://localhost:%s\n", appPort)
	log.Fatal(http.ListenAndServe(addr, r))
}

func login(w http.ResponseWriter, r *http.Request) {
    var creds Credentials
    json.NewDecoder(r.Body).Decode(&creds)

	storedHash := ""
	err := db.QueryRow("SELECT password FROM user WHERE email = ?", creds.Email).Scan(&storedHash)
	if err != nil {
		if err == sql.ErrNoRows {
			http.Error(w, "user not found", http.StatusUnauthorized)
		} else {
			http.Error(w, err.Error(), http.StatusInternalServerError)
		}
		return
	}

	if err := bcrypt.CompareHashAndPassword([]byte(storedHash), []byte(creds.Password)); err != nil {
		http.Error(w, "invalid password", http.StatusUnauthorized)
		return
	}

    expirationTime := time.Now().Add(2 * time.Minute)
    claims := &Claims{
        Email: creds.Email,
        RegisteredClaims: jwt.RegisteredClaims{
            ExpiresAt: jwt.NewNumericDate(expirationTime),
        },
    }

    token := jwt.NewWithClaims(jwt.SigningMethodHS256, claims)
    tokenString, err := token.SignedString(jwtKey)
    if err != nil {
        w.WriteHeader(http.StatusInternalServerError)
        return
    }

    w.Header().Set("Content-Type", "application/json")
    json.NewEncoder(w).Encode(map[string]string{"token": tokenString})
}

func authenticate(next http.HandlerFunc) http.HandlerFunc {
    return func(w http.ResponseWriter, r *http.Request) {
        tokenStr := r.Header.Get("Authorization")
        if tokenStr == "" {
            http.Error(w, "missing token", http.StatusUnauthorized)
            return
        }

        tokenStr = tokenStr[len("Bearer "):]

        claims := &Claims{}
        token, err := jwt.ParseWithClaims(tokenStr, claims, func(token *jwt.Token) (interface{}, error) {
            return jwtKey, nil
        })

        if err != nil || !token.Valid {
            http.Error(w, "invalid token", http.StatusUnauthorized)
            return
        }

        next.ServeHTTP(w, r)
    }
}

func getUsers(w http.ResponseWriter, r *http.Request) {
    rows, err := db.Query("SELECT id, email FROM user")
    if err != nil {
        http.Error(w, err.Error(), http.StatusInternalServerError)
        return
    }
    defer rows.Close()

    var users []User
    for rows.Next() {
        var user User
        if err := rows.Scan(&user.ID, &user.Email); err != nil {
            http.Error(w, err.Error(), http.StatusInternalServerError)
            return
        }
        users = append(users, user)
    }

    w.Header().Set("Content-Type", "application/json")
    json.NewEncoder(w).Encode(users)
}

func getUserByID(w http.ResponseWriter, r *http.Request) {
    idStr := mux.Vars(r)["id"]
    id, _ := strconv.Atoi(idStr)

    var user User
    err := db.QueryRow("SELECT id, email, is_verified, created_at, roles FROM user WHERE id = ?", id).Scan(&user.ID, &user.Email, &user.Verified, &user.Created, &user.Roles)
    if err != nil {
        if err == sql.ErrNoRows {
            http.NotFound(w, r)
            return
        }
        http.Error(w, err.Error(), http.StatusInternalServerError)
        return
    }

    w.Header().Set("Content-Type", "application/json")
    json.NewEncoder(w).Encode(user)
}
