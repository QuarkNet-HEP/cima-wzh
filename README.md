# CIMA - WZH
WZH version of CMS Instrument for Masterclass Analysis (CIMA) (2019)

## Running in a Docker container

### Install Docker

If you haven't done so already, install [Docker](https://docs.docker.com/install/)

### Clone this repository

`git clone https://github.com/tpmccauley/cima-wzh.git`

### Change your branch

```
cd ./cima-wzh
git checkout docker
```

### Set login details

Copy `./config/mc.config.example` to `./config/mc.config` and fill in the information for `$db_config` and `$auth_config`

### Copy sql database 

In `docker-compose.yml` it is assumed that the database is in `./cima-wzh`. If you want to use another location then 
change the it under `volumes` in `docker-compose.yml`

### Build and run

In `./cima-wzh` run the commands: 

```
docker-compose build
docker-compose up
```

and then go to `http://localhost:8080`
