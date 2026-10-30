# Makefile

# Running dev environment
up:
	make install && \
	docker-compose -f build/dev/docker-compose.yml -p f24 up --build --force-recreate --remove-orphans -d gateway

# Stopping dev environment
down:
	docker-compose -f build/dev/docker-compose.yml -p f24 down

# Install requirements
install:
	docker-compose -f build/dev/docker-compose.yml -p f24 run --rm backend composer install

# Run tests
test:
	docker-compose -f build/dev/docker-compose.yml -p f24 run --rm backend vendor/bin/phpunit tests
