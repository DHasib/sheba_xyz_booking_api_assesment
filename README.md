# Sheba XYZ Booking API Assesment- Local Development Guide

A simple Laravel-based RESTful API for managing bookings in the Sheba XYZ Int. Assesment system.

 

This project ships with a **Makefile** that hides almost every Docker / Laravel command you normally have to remember. After cloning, you can be up and running with **one line** to setup everything will be done by automation.



**Prerequisites**

| Tool                  | Version (‑‑minimum)                                                         | Notes                                                                                            |
| --------------------- | --------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------ |
| **Docker Desktop**    | 20.10+                                                                      | Linux users: the Docker Engine daemon & CLI.                                                     |
| **Docker‑Compose v2** | Bundled with Docker Desktop ≥ 4.13 <br>or `docker compose` plugin on Linux. |                                                                                                  |
| **GNU Make**          | 4.x                                                                         | Comes pre‑installed on macOS & most Linux distros. Windows: install via WSL or make for Windows. |

> **No local PHP, Composer, MySQL, or Node required** – everything runs inside the `booking_api` container.



## Table of Contents

- [Setup & Run Instructions](#setup--run-instructions)  
- [API Documentation](#api-documentation)
  - [Admin Credential](#admin-credential)  
  - [Authentication](#authentication)  
  - [Bookings](#bookings)
  - [Project Entity Relationship Diagram (ERD)](#erd)  
- [Running Tests](#running-tests)  


## Setup & Run Instructions

 **1 · Clone the repo**

```bash
git clone https://github.com/DHasib/sheba_xyz_booking_api_assesment.git
cd booking‑api
```



**2 · One‑shot setup (build → up → composer install → .env → migrate & seed)**

```bash
make setup
```

Behind the scenes this will:

1. `docker compose build booking_api` – build the PHP/Laravel image
2. `docker compose up -d booking_api` – start the app & its dependencies
3. `composer install` – inside the container
4. Copy `.env.dev → .env` if no `.env` exists
5. `php artisan migrate:fresh --seed` – wipe & seed the database

When the command finishes, the API is available at **[http://localhost:8008](http://localhost:8008)** (or the port you mapped in `docker‑compose.yml`).

---

**3 · Daily workflow cheatsheet**

| Task                                    | Command                 |
| --------------------------------------- | ----------------------- |
| Rebuild images after Dockerfile changes | `make build`            |
| Start/stop containers                   | `make up` / `make stop` |
| Drop DB & reseed                        | `make migrate`          |
| Run PHPUnit test suite                  | `make test`             |
| Clear Laravel caches                    | `make clear`            |
| Tail the Laravel log                    | `make log`              |
| Tear everything down                    | `make down`             |

> You can override the container name on the fly:<br>`SERVICE=my_alt_service make migrate`

---

**4 · Troubleshooting**

### Containers can’t see MySQL (`getaddrinfo ENOTFOUND mysqlDB`)

* Ensure `DB_HOST` in `.env` matches the DB service in `docker‑compose.yml` (e.g. `mysqlDB`).
* The DB service **must** have a health‑check, and `booking_api` should have `depends_on: condition: service_healthy`.

### Composer memory errors during `make setup`

Use the **swapfile** setting in Docker Desktop (or allocate more memory) – Laravel’s optimized autoloader needs \~1 GB during the install.

### Changing `.env`

After editing `.env`, run `make clear` to flush cached config.

---

**5 · CI / CD hint**

Because the Makefile is deterministic, GitHub Actions / GitLab CI only need:

```yaml
steps:
  - uses: actions/checkout@v4
  - uses: docker/setup-buildx-action@v3
  - run: make setup
  - run: make test
```


## Api-Documentation

## Admin-Credential

**It will Create Automatically When You Run Make Setup Command**

```bash
'name' = admin
'email' = admin@example.com
'password' = P@ssword
```

## ERD
<p align="center">
  <img
    src="https://raw.githubusercontent.com/DHasib/sheba_xyz_booking_api_assesment/main/Booking_ERD.png"
    width="400"
    alt="Project ERD"
  />
</p>



## Running-Tests

**One‑shot To Run All the Test**

```bash
make test
```
