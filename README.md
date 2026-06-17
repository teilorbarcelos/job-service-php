# Job Service PHP

> Esqueleto (boilerplate) para execução de jobs agendados em PHP 8.3+.
> Conecta-se ao `backend-php-slim` para consumir PostgreSQL, Redis e RabbitMQ.

---

## Stack

| Camada | Tecnologia |
|--------|------------|
| Runtime | PHP 8.3+ |
| ORM | Laravel Eloquent (standalone, Capsule) |
| BD | PostgreSQL 15 (dev/prod) / SQLite (testes) |
| Cache | Redis via phpredis |
| Mensageria | RabbitMQ (php-amqplib) |
| Cron | dragonmantank/cron-expression |
| Logger | Monolog JSON estruturado (stdout) |
| Qualidade | PHPStan 9 + PHPUnit + SonarQube |

---

## Começando

```bash
# 1. Instalar dependências
composer install

# 2. Copiar configuração
cp .env.example .env

# 3. Subir infraestrutura (PostgreSQL + Redis + RabbitMQ)
make infra-up

# 4. Rodar o scheduler
make dev
```

---

## Comandos

```bash
make dev                # Roda o scheduler (PHP src/Application.php)
make test               # Testes sem coverage
make coverage           # Testes com cobertura via Docker
make lint               # PHPStan nível 9
make check              # lint + test + coverage

make infra-up           # Sobe PG + Redis + Rabbit (docker-compose.infra.yml)
make infra-stop         # Para infraestrutura
make infra-down         # Remove containers da infra
make infra-clean        # Remove volumes/imagens da infra

make generate-job       # Gera job (name=... schedule=... description=...)
make build              # Builda imagem Docker
make run                # Roda container Docker

make sonar              # Scan SonarQube
make clean              # Remove artifacts
```

---

## Adicionar um novo job

### Via generator (recomendado)

```bash
make generate-job name=CleanupOldRecords schedule="0 3 * * *" description="Remove registros com mais de 90 dias"
```

Isso cria:

- `src/Jobs/CleanupOldRecordsJob.php` — esqueleto do job com `BaseJob`
- `tests/Jobs/CleanupOldRecordsJobTest.php` — 5 testes unitários
- Registra automaticamente em `src/Jobs/register-jobs.php`

### Manualmente

1. Criar `src/Jobs/MeuJob.php` extends `BaseJob`
2. Implementar `getName()`, `getSchedule()`, `getDescription()`, `handle(JobContext $ctx)`
3. Adicionar em `src/Jobs/register-jobs.php`
4. Criar `tests/Jobs/MeuJobTest.php`

---

## Arquitetura

```
src/
├── Application.php                 # Entry point (boot, conexões, scheduler)
├── Core/                           # Núcleo de jobs
│   ├── BaseJob.php                 # Classe abstrata (lifecycle, logs, try/catch)
│   ├── JobContext.php              # { Logger, Signal }
│   ├── JobResult.php               # Value object
│   ├── JobStatus.php               # Enum (SUCCESS, FAILED, CANCELLED, TIMEOUT)
│   ├── JobSignal.php               # AbortSignal (pcntl)
│   ├── JobInfo.php                 # Info do job para listagem
│   ├── CronAdapter.php             # Interface
│   ├── DragonmantankCronAdapter.php# Implementação com cron-expression
│   ├── Scheduler.php               # Scheduler (start/tick/stop)
│   └── Exceptions/                 # AppError, ConfigurationError, ConnectionError
├── Infrastructure/                 # Provedores de serviços
│   ├── Database/DatabaseProvider.php   # Capsule (Eloquent)
│   ├── Redis/RedisProvider.php         # phpredis singleton
│   ├── Messaging/RabbitMQProvider.php  # Publisher + health check
│   └── Health/                        # HealthCheckResult, DefaultHealthChecker
├── Jobs/                           # Jobs do projeto
│   ├── HealthCheckJob.php             # Diagnóstico PG/Redis/Rabbit
│   └── register-jobs.php              # Registro central
├── Shared/                         # Utilitários
│   ├── Config/EnvValidator.php        # Validação + AppSettings
│   └── Utils/
│       ├── LoggerFactory.php          # Monolog JSON
│       ├── SignalManager.php          # Timeout via pcntl_alarm
│       └── ShutdownHandler.php        # Graceful shutdown (SIGTERM/SIGINT)
tests/
├── Core/                           # Testes do core
├── Infrastructure/                 # Testes dos provedores
├── Jobs/                           # Testes dos jobs
└── Shared/                         # Testes de config/utils
```

### Fluxo de vida do scheduler

```
Application.php
  │
  ├─ EnvValidator::load()         ← .env
  ├─ LoggerFactory::create()      ← Monolog stdout
  ├─ DatabaseProvider::getInstance()
  ├─ RedisProvider::getInstance()
  ├─ RabbitMQProvider::connect()
  ├─ registerJobs()               ← Lista de jobs
  │
  └─ while (true)
       ├─ tick()                   ← Verifica se algum job deve rodar
       ├─ execute(name, job)       ← Seta timeout via pcntl_alarm
       │   └─ job->run(context)    ← try/catch, logs, stopwatch
       └─ sleep(1)

SIGTERM/SIGINT
  └─ ShutdownHandler
       ├─ scheduler->stop()
       ├─ scheduler->waitForRunningJobs()
       ├─ rabbitmq->close()
       ├─ DatabaseProvider::close()
       └─ RedisProvider::resetInstance()
```

---

## Variáveis de ambiente (.env)

```
# App
APP_ENV=development|testing|production
APP_DEBUG=true|false
LOG_LEVEL=info|debug|error
SHUTDOWN_TIMEOUT_MS=30000
JOB_EXECUTION_TIMEOUT_MS=300000

# Database (gerenciado pelo backend-php-slim)
DB_DRIVER=pgsql|sqlite
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=backend_php_slim_db
DB_USERNAME=postgres
DB_PASSWORD=postgrespw

# Redis
REDIS_HOST=localhost
REDIS_PORT=6379

# RabbitMQ
MESSAGING_ENABLED=false|true
RABBIT_HOST=localhost
RABBIT_PORT=5672
RABBIT_USER=guest
RABBIT_PASSWORD=guest

# Jobs
HEALTH_CHECK_CRON=*/1 * * * *
HEALTH_CHECK_ENABLED=true
```

---

## Testes

```bash
make test         # 125 testes, sem coverage
make coverage     # via Docker com pcov (cobertura 100% nos arquivos críticos)
```

**Cobertura:** 100% nos arquivos críticos (Core, Jobs, Shared/Config). Arquivos de infraestrutura (providers DB/Redis/RabbitMQ) toleram linhas defensivas não testáveis sem serviços reais.

---

## Infraestrutura local

```bash
make infra-up     # docker compose -f docker-compose.infra.yml up -d
make infra-stop   # docker compose -f docker-compose.infra.yml stop
make infra-down   # docker compose -f docker-compose.infra.yml down

# Serviços:
#   PostgreSQL: localhost:5432 (postgres/postgrespw)
#   Redis:      localhost:6379
#   RabbitMQ:   localhost:5672 (guest/guest) | Management: localhost:15672
```

---

## Docker

```bash
make build        # docker build -t job-service-php .
make run          # docker run --rm --network host job-service-php
```

---

## Generator de jobs

```bash
make generate-job name=CleanupOldRecords
make generate-job name=SendReport schedule="0 9 * * 1" description="Weekly report"
```

O generator:

1. Cria `src/Jobs/{Name}Job.php` com o esqueleto completo
2. Cria `tests/Jobs/{Name}JobTest.php` com 5 testes
3. Atualiza `src/Jobs/register-jobs.php` com `use` e instância

---

## SonarQube

```bash
make coverage        # Gera coverage/clover.xml
make sonar           # Roda sonar-scanner → http://localhost:9000
```

Pré-requisitos:

- SonarQube rodando em `http://localhost:9000`
- Token em `SONAR_TOKEN` (ou usa o default em `scripts/sonar-scan.sh`)
- Sonar scanner em `/home/teilor/.sonar/native-sonar-scanner/...`

---

## Qualidade

- **PHPStan nível 9** — análise estática sem erros
- **PHPUnit 125 testes** — 100% coverage em arquivos críticos
- **SonarQube** — Quality Gate OK (0 bugs, 0 vulnerabilidades)
- **Pre-commit hook** — PHPStan + testes via Husky
- **CI** — GitHub Actions com PHPStan + PHPUnit + Docker build
