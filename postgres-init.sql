-- Runs once, on first container boot only (docker-entrypoint-initdb.d).
-- POSTGRES_DB in docker-compose.yml creates `batu`; the test suite needs
-- its own database on the same instance (phpunit.xml, ADR-003).
CREATE DATABASE batu_test OWNER batu;
