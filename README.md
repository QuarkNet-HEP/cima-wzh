# CIMA - WZH
WZH version of CMS Instrument for Masterclass Analysis (CIMA) (2019)

## Running in a Docker container

### Install and start Docker

If you haven't done so already, install [Docker](https://docs.docker.com/install/) and [docker-compose](https://docs.docker.com/compose/install/).

In some installations and cases you may have to manually start the Docker daemon. On the command line you may do so with the `dockerd` command. For more information visit [here](https://docs.docker.com/config/daemon/).

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

### Issues and troubleshooting

*  I receive an error message like the one below:
```
ERROR: Couldn't connect to Docker daemon at http+docker://localunixsocket - is it running?
```

Your Docker daemon is not running. In the command line you can start it with the `dockerd` command. For more information visit [here](https://docs.docker.com/config/daemon/).

*  When I run `docker-compose build` I get the following error:
```
ERROR: Version in "./docker-compose.yml" is unsupported.
```

Your version of Docker is incompatible with the Compose file format version, which is indicated in `docker-compose.yml` as `version`. Check [here](https://docs.docker.com/compose/compose-file/) to see which version of Compose files is compatible with your version of Docker, which you can find out by running `docker --version`. You can either upgrade your version of Docker (recommended) or downgrade the `version` in `docker-compose.yml` to comply with your version of Docker (may or may not work depending on the contents of `docker-compose.yml`).

