# OverseerV2

The second iteration of the Overseer games.

The database dump can be found at `database.sql`. Here, it's the master branch that contains the last official live version.

There's a `.env.dist` you can fill out a `.env` from.

Remember to run `composer install`.

The repository also contains the status of the then upcoming Overseer v2.5, on the dev branch and a couple of feature branches.

Godspeed.

## Setup video

https://youtu.be/sNQw6eO1aJ0

## Dockerized Setup

1. Install Docker (for dev work, you can also look at Docker Desktop)
2. Copy `.env.dist` to `.env`, and fill up the credentials appropriately
3. Run the following command in the base of this repository: `docker compose --profile dev up -d --build`
4. Wait for the build to finish
5. The website should now be accessible in `http://localhost:9000`, and the database should be accessible

> If you want to run the website without building again in the future, do `docker compose --profile dev up -d` instead.
