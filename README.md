# Job Service PHP

> Esqueleto (boilerplate) para execução de jobs agendados em PHP 8.3+.
> Conecta-se ao `backend-php-slim` para consumir PostgreSQL, Redis e RabbitMQ.

## Stack

| Camada | Tecnologia |
|--------|------------|
| Runtime | PHP 8.3 |
| ORM | Laravel Eloquent (standalone, Capsule) |
| BD | PostgreSQL 15 (dev/prod) / SQLite (testes) |
| Cache/Session | Redis via phpredis |
| Mensageria | RabbitMQ (php-amqplib) |
| Cron | dragonmantank/cron-expression |
| Logger | Monolog JSON estruturado |
| Qualidade | PHPStan 9 + PHPUnit 100% + SonarQube |

## Comandos

```bash
make infra-up        # Sobe PG + Redis + Rabbit
make dev             # Roda o scheduler local
make test            # Testes sem coverage
make coverage        # Testes + 100% coverage
make lint            # PHPStan nível 9
make generate-job    # Gera job (name=...)
make sonar           # Scan SonarQube
```

## Adicionar um novo job

```bash
make generate-job name=CleanupOldRecords schedule="0 3 * * *" description="Remove registros antigos"
```

Isso cria `src/Jobs/CleanupOldRecordsJob.php` e registra automaticamente.

## Estrutura

```
src/
├── Application.php             # Entry point
├── Core/                       # BaseJob, Scheduler, CronAdapter
├── Infrastructure/             # Database, Redis, RabbitMQ, Health
├── Jobs/                       # HealthCheckJob + register-jobs
├── Shared/                     # Config, Utils
tests/                          # 100% cobertura
```

