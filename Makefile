# Makefile

# Running dev environment
up:
	docker-compose -f build/dev/docker-compose.yml -p f24 up --build --force-recreate --remove-orphans -d backend

# Stopping dev environment
down:
	docker-compose -f build/dev/docker-compose.yml -p f24 down

# Install requirements
install:
	make up && docker-compose -f build/dev/docker-compose.yml -p f24 run --rm backend composer install
