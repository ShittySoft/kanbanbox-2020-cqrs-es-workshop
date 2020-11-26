#!/usr/bin/env sh

docker build -t kanbanbox-test-cqrs .

docker run -ti \
  -v `pwd`:/app \
  -p 8080:8080 \
  kanbanbox-test-cqrs \
  bash -c "cd /app && composer install && ./run.sh"
