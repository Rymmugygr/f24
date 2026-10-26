# Makefile

# Running dev environment
up:
	docker-compose -f build/dev/docker-compose.yml -p f24 up --build --force-recreate --remove-orphans -d backend

# Stopping dev environment
down:
	docker-compose -f build/dev/docker-compose.yml -p f24 down

# Create empty project
# create:
#	docker-compose -f build/dev/docker-compose.yml -p f24 build && docker-compose -f build/dev/docker-compose.yml -p f24 run backend composer create-project symfony/skeleton:"7.4.*" a24
