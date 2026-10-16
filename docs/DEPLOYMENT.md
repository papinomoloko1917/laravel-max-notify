# Установка max-notify на чистый Ubuntu Server

Эта инструкция описывает первоначальную production-установку на MiniPC с Ubuntu Server 26.04 LTS в одной локальной сети с Dahua. Публичный домен и входящие подключения из интернета не требуются.

Production-схема отделена от Sail-разработки:

- Caddy принимает HTTPS-запросы панели на порту `443` и выдаёт сертификат от собственной локальной CA;
- Caddy принимает на порту `80` только `/webhooks/dahua`, не записывая URL с секретным webhook-токеном в access log; PHP-FPM access log также отключён;
- PHP-FPM выполняет Laravel-код и не публикуется в локальную сеть;
- отдельные контейнеры выполняют очередь и Laravel Scheduler;
- PostgreSQL хранится в Docker volume и не публикует порт `5432`;
- frontend собирается при создании неизменяемого application image;
- Adminer, Vite, Xdebug, bind mount исходников и `artisan serve` в production отсутствуют.

`compose.yaml` предназначен только для локальной разработки. На сервере используется исключительно `compose.production.yaml`.

## 1. Что подготовить заранее

До установки запишите:

- постоянный IP MiniPC, например `10.10.0.50`;
- маску/подсеть, шлюз и DNS локальной сети;
- IP камеры или NVR;
- Git URL проекта и проверенный commit/тег;
- email и длинный пароль первого администратора;
- место для резервных копий **на другом физическом устройстве**.

Лучше закрепить IP MiniPC в DHCP-настройках роутера по MAC-адресу. Если используется статический Netplan, сначала узнайте имя интерфейса командой `ip -br address`, затем меняйте существующий файл в `/etc/netplan/`. Не копируйте чужое имя интерфейса или адреса вслепую. После `sudo netplan try` подтверждайте конфигурацию, только если SSH-соединение сохранилось.

Сервер должен иметь:

- исходящий DNS и HTTPS-доступ к GitHub, Docker Hub, репозиториям Ubuntu и `platform-api2.max.ru`;
- сетевой доступ к HTTP API каждой настроенной камеры/NVR;
- входящий доступ от камеры/NVR к TCP `80`;
- входящий доступ администраторских компьютеров к TCP `443` и SSH;
- правильное время и часовой пояс `Europe/Moscow`.

Проверьте время:

```bash
timedatectl
sudo timedatectl set-timezone Europe/Moscow
sudo timedatectl set-ntp true
```

## 2. Базовая подготовка Ubuntu

Войдите под обычным пользователем с `sudo`, обновите систему и установите вспомогательные пакеты:

```bash
sudo apt update
sudo apt full-upgrade -y
sudo apt install -y ca-certificates curl git openssl
sudo reboot
```

После перезагрузки снова подключитесь по SSH. Желательно использовать SSH-ключ, запретить вход `root` по SSH и не выставлять SSH или веб-порты в интернет на роутере.

Запишите фактические сведения о сервере — они пригодятся при диагностике и настройке сети:

```bash
cat /etc/os-release
uname -m
ip -br address
ip route
free -h
df -h /
```

Для MiniPC ожидается архитектура `x86_64`/`amd64`. До изменения SSH-настроек сначала проверьте вход по ключу во втором отдельном терминале, иначе можно потерять удалённый доступ.

## 3. Установка Docker Engine из официального репозитория

Удалите только действительно установленные конфликтующие пакеты. На чистом сервере список может оказаться пустым — это нормально:

```bash
conflicting_packages="$(dpkg --get-selections \
  docker.io docker-compose docker-compose-v2 docker-doc docker-buildx \
  podman-docker containerd runc 2>/dev/null | awk '{print $1}')"

if [ -n "$conflicting_packages" ]; then
  sudo apt remove -y $conflicting_packages
fi
```

Подключите официальный Docker-репозиторий:

```bash
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc

sudo tee /etc/apt/sources.list.d/docker.sources > /dev/null <<EOF
Types: deb
URIs: https://download.docker.com/linux/ubuntu
Suites: $(. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}")
Components: stable
Architectures: $(dpkg --print-architecture)
Signed-By: /etc/apt/keyrings/docker.asc
EOF

sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo systemctl enable --now docker
sudo docker run --rm hello-world
sudo docker compose version
```

Ubuntu 26.04 LTS сейчас входит в официальный список поддерживаемых Docker Engine систем. Не используйте `curl https://get.docker.com | sh` для production-сервера: официальный convenience script предназначен прежде всего для разработки и тестирования.

Добавьте своего пользователя в группу Docker и **полностью выйдите из SSH-сеанса**, затем войдите снова:

```bash
sudo usermod -aG docker "$USER"
exit
```

После повторного входа проверьте `docker ps` без `sudo`. Членство в группе `docker` практически даёт root-доступ, поэтому добавляйте туда только доверенных операторов.

## 4. Получение проекта

На сервере:

```bash
sudo mkdir -p /opt/max-notify
sudo chown "$USER":"$USER" /opt/max-notify
git clone https://github.com/papinomoloko1917/laravel-max-notify.git /opt/max-notify/app
cd /opt/max-notify/app
git status
git log -1 --oneline
test -f compose.production.yaml
test -f .env.production.example
test -f docker/production/Dockerfile
```

Устанавливайте только commit, в котором уже есть `compose.production.yaml`, `docker/production/` и `.env.production.example`. Незакоммиченные файлы с другого компьютера Git не переносит: сначала они должны пройти тесты, попасть в осмысленный commit и быть отправлены в GitHub.

## 5. Production-переменные

Создайте закрытый файл окружения:

```bash
cp .env.production.example .env.production
chmod 600 .env.production
nano .env.production
```

Обязательно замените:

- `SERVER_IP` — постоянный IP MiniPC;
- `APP_URL` — `https://<IP MiniPC>`;
- `DAHUA_WEBHOOK_BASE_URL` — `http://<IP MiniPC>`;
- `DB_PASSWORD` — отдельный длинный случайный пароль;
- при необходимости имя БД и пользователя.

Сгенерировать пароль PostgreSQL можно так:

```bash
openssl rand -base64 36
```

Оставьте `APP_KEY=` пустым до отдельного шага ниже. Не копируйте ключ с dev-машины. `MAX_TOKEN` тоже можно оставить пустым: после входа его безопаснее проверить и сохранить через страницу настройки MAX-бота.

Проверьте важные production-значения:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://10.10.0.50
APP_TIMEZONE=Europe/Moscow
LOG_CHANNEL=stderr
DB_HOST=pgsql
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
DAHUA_WEBHOOK_BASE_URL=http://10.10.0.50
```

Не добавляйте `.env.production` в Git, не отправляйте его в чат и не храните вместе с обычной копией репозитория.

## 6. Сборка образов и APP_KEY

Проверьте итоговую Compose-конфигурацию и соберите образы:

```bash
docker compose --env-file .env.production -f compose.production.yaml config --quiet
docker compose --env-file .env.production -f compose.production.yaml build
```

Сгенерируйте ключ Laravel внутри собранного образа:

```bash
docker compose --env-file .env.production -f compose.production.yaml run --rm --no-deps app php artisan key:generate --show
```

Скопируйте всю строку `base64:...` в `APP_KEY` файла `.env.production`. Этот ключ нельзя менять после начала работы: им зашифрованы Digest-пароли камер, webhook-токены и токен MAX. Потеря `APP_KEY` означает потерю доступа к этим значениям.

Сохраните защищённую копию `.env.production` отдельно от MiniPC.

## 7. Первый запуск и база данных

Сначала запустите PostgreSQL и PHP-приложение:

```bash
docker compose --env-file .env.production -f compose.production.yaml up -d pgsql app
docker compose --env-file .env.production -f compose.production.yaml ps
```

Примените миграции и создайте production-кеши Laravel:

```bash
docker compose --env-file .env.production -f compose.production.yaml exec app php artisan migrate --force
docker compose --env-file .env.production -f compose.production.yaml exec app php artisan optimize
```

Не запускайте `db:seed`: тестовый пользователь создаётся только в `local`, а production-администратор создаётся интерактивной командой:

```bash
docker compose --env-file .env.production -f compose.production.yaml exec app php artisan admin:create
```

Команда запросит имя, email и дважды пароль. Пароль не является аргументом командной строки и не попадёт в shell history. Минимальная длина — 12 символов.

Теперь запустите весь набор сервисов:

```bash
docker compose --env-file .env.production -f compose.production.yaml up -d
docker compose --env-file .env.production -f compose.production.yaml ps
```

Ожидаемое состояние: `app`, `queue`, `scheduler`, `web`, `pgsql` работают; PostgreSQL имеет статус healthy. Наружу опубликованы только `80` и `443` на `SERVER_IP`.

## 8. Доверие локальному HTTPS-сертификату

Caddy создаёт собственный локальный корневой сертификат и выдаёт сертификат для IP MiniPC. Храните экспортируемую копию вне Git-репозитория и копируйте **только публичный root certificate**:

```bash
mkdir -p /opt/max-notify/certificates
chmod 700 /opt/max-notify/certificates

docker compose --env-file .env.production -f compose.production.yaml \
  cp web:/data/caddy/pki/authorities/local/root.crt \
  /opt/max-notify/certificates/max-notify-local-ca.crt

chmod 644 /opt/max-notify/certificates/max-notify-local-ca.crt
```

Передайте `/opt/max-notify/certificates/max-notify-local-ca.crt` на каждый доверенный администраторский компьютер по защищённому каналу.

На Windows запустите `certmgr.msc` для текущего пользователя или `mmc` → Certificates для компьютера, импортируйте сертификат в «Доверенные корневые центры сертификации», затем полностью перезапустите браузер. Устанавливайте этот root CA только на устройствах, которым вы доверяете.

На Ubuntu-клиенте:

```bash
sudo cp max-notify-local-ca.crt /usr/local/share/ca-certificates/max-notify-local-ca.crt
sudo update-ca-certificates
```

После установки CA откройте:

```text
https://<IP MiniPC>/login
```

Не обходите предупреждение браузера на постоянной основе. Если certificate warning остаётся, проверьте, что `SERVER_IP`, `APP_URL` и адрес в браузере совпадают буквально.

## 9. Первичная настройка приложения

В панели выполните последовательно:

1. Войдите под созданным администратором.
2. Откройте настройку MAX-бота, введите токен и дождитесь успешной проверки `GET /me`.
3. Создайте клиента и укажите его MAX chat ID.
4. Создайте камеру: название, активность, адрес устройства вида `http://10.10.0.181` без `/cgi-bin/snapshot.cgi`, Digest login/password и канал снимка.
5. Назначьте клиента камере.
6. На странице правил камеры добавьте нужные типы событий.
7. Скопируйте целиком готовый URL. Благодаря `DAHUA_WEBHOOK_BASE_URL` он будет HTTP, хотя панель работает по HTTPS.
8. В Dahua вставьте URL в «Отправить команду» и отключите device-side Authentication: секретный токен уже находится в URL.

HTTP-порт не показывает панель и для любого пути кроме `/webhooks/dahua` возвращает `404`. Access logs HTTP listener и PHP-FPM отключены, а HTTPS listener пропускает webhook-запросы при журналировании, чтобы query-параметр `token` не сохранялся. Безопасные прикладные результаты продолжают попадать в Laravel log без секретов.

## 10. Проверка после установки

Проверьте health route с администраторского компьютера:

```bash
curl --cacert max-notify-local-ca.crt https://<IP MiniPC>/up
```

Ожидается ответ `200` и текст `Application up`.

Проверьте процессы и журналы:

```bash
docker compose --env-file .env.production -f compose.production.yaml ps
docker compose --env-file .env.production -f compose.production.yaml logs --tail=100 app
docker compose --env-file .env.production -f compose.production.yaml logs --tail=100 queue
docker compose --env-file .env.production -f compose.production.yaml exec app php artisan queue:failed
docker compose --env-file .env.production -f compose.production.yaml exec app php artisan schedule:list
```

До сохранения реального MAX-токена отдельно проверьте TLS из контейнера очереди:

```bash
docker compose --env-file .env.production -f compose.production.yaml exec queue \
  php artisan tinker --execute="dump(\Illuminate\Support\Facades\Http::get(config('services.max.base_url').'/me')->status());"
```

Без токена ожидается HTTP-статус `401`. Он означает, что HTTPS-соединение и проверка сертификата состоялись, а MAX ожидаемо отклонил неаутентифицированный запрос. Ошибка `cURL error 60` означает, что образ собран без актуального доверенного CA. На чистом сервере повторите сборку без кеша и пересоздайте контейнеры:

```bash
docker compose --env-file .env.production -f compose.production.yaml build --no-cache
docker compose --env-file .env.production -f compose.production.yaml up -d --force-recreate
```

Затем выполните одно контролируемое Dahua-событие и убедитесь, что:

- событие появилось в истории;
- статус перешёл из «Ожидает» в итоговый;
- очередь снова пуста;
- клиент получил текст и снимок в MAX;
- новые строки не появились в `failed_jobs`.

## 11. Firewall и границы локальной сети

Ни `5432`, ни `9000` production Compose не публикует. Порты `80` и `443` привязаны только к `SERVER_IP`, но по умолчанию доступны всей сети, имеющей маршрут к этому IP.

Минимально обеспечьте на роутере отсутствие port forwarding из интернета. В идеале разрешите:

- TCP `80` только от IP камеры/NVR;
- TCP `443` только от администраторской подсети/устройств;
- SSH только от администраторских устройств.

Важно: опубликованные Docker-порты могут обходить обычные ожидания от UFW. Ограничения контейнерных портов нужно проектировать через Docker firewall (`DOCKER-USER` при iptables backend либо соответствующую nftables-цепочку). Не вставляйте готовое правило до выяснения имени LAN-интерфейса, реальной подсети администратора и IP всех источников Dahua: ошибка может отрезать webhook или SSH. Эти значения остаются обязательной частью финального hardening сервера.

## 12. Резервные копии

Docker volume на том же SSD не является резервной копией. Нужен внешний диск, NAS или другой защищённый узел.

Пример ручной логической копии PostgreSQL в уже смонтированный каталог `/mnt/max-notify-backups`:

```bash
mkdir -p /mnt/max-notify-backups
docker compose --env-file .env.production -f compose.production.yaml exec -T pgsql \
  pg_dump -U max_notify -d max_notify --format=custom \
  > "/mnt/max-notify-backups/max-notify-$(date +%F-%H%M%S).dump"
```

Если вы изменили `DB_USERNAME` или `DB_DATABASE`, измените их и в этой команде. Проверяйте, что файл не пуст и копируется с MiniPC на другой носитель.

Отдельно и безопасно сохраните:

- `.env.production`, особенно `APP_KEY`;
- дамп PostgreSQL;
- Caddy volume или локальный root CA, чтобы после восстановления не переустанавливать новый CA на всех компьютерах;
- точный Git commit приложения.

Резервная копия считается рабочей только после проверенного восстановления на отдельной тестовой БД/машине. Автоматический график, шифрование, срок хранения и конкретное внешнее место нельзя выбрать до определения backup destination.

## 13. Обновление приложения

Перед обновлением убедитесь, что изменения протестированы и отправлены в Git. На сервере:

```bash
cd /opt/max-notify/app
git fetch --prune
git status
git pull --ff-only
git log -1 --oneline
docker compose --env-file .env.production -f compose.production.yaml build
docker compose --env-file .env.production -f compose.production.yaml exec app php artisan down
docker compose --env-file .env.production -f compose.production.yaml stop queue scheduler
docker compose --env-file .env.production -f compose.production.yaml run --rm app php artisan migrate --force
docker compose --env-file .env.production -f compose.production.yaml up -d --remove-orphans
docker compose --env-file .env.production -f compose.production.yaml exec app php artisan optimize
docker compose --env-file .env.production -f compose.production.yaml exec app php artisan queue:restart
docker compose --env-file .env.production -f compose.production.yaml exec app php artisan up
docker compose --env-file .env.production -f compose.production.yaml ps
```

При миграциях сначала создавайте резервную копию. Откат Git-кода не всегда откатывает схему БД, поэтому rollback должен учитывать совместимость миграций и восстановление дампа.

## 14. Обычные команды оператора

Все команды выполняются из `/opt/max-notify/app`:

```bash
# Состояние
docker compose --env-file .env.production -f compose.production.yaml ps

# Последние журналы
docker compose --env-file .env.production -f compose.production.yaml logs --tail=200 app queue web

# Следить за worker в реальном времени
docker compose --env-file .env.production -f compose.production.yaml logs -f queue

# Перезапустить только worker
docker compose --env-file .env.production -f compose.production.yaml restart queue

# Остановить контейнеры, сохранив БД и CA
docker compose --env-file .env.production -f compose.production.yaml down

# Запустить снова
docker compose --env-file .env.production -f compose.production.yaml up -d
```

Никогда не выполняйте `docker compose down -v`: ключ `-v` удалит volumes, включая PostgreSQL. Не запускайте `queue:retry` для `ProcessCameraEvent`: после частичной доставки это может повторно отправить уведомления. Подробности находятся в `docs/OPERATIONS.md`.

## 15. Что ещё нужно завершить перед окончательным вводом в эксплуатацию

Технический deployment теперь воспроизводим, но конкретная площадка всё ещё требует зафиксировать:

- постоянные IP MiniPC и NVR/камер;
- разрешённую администраторскую подсеть;
- постоянные Docker firewall-правила и их восстановление после reboot;
- внешний backup destination, расписание, шифрование и тест восстановления;
- наличие UPS и поведение после потери питания;
- контроль свободного места и оповещение о неработающем worker/росте `failed_jobs`.

## Официальные справочные материалы

- [Установка Docker Engine на Ubuntu](https://docs.docker.com/engine/install/ubuntu/)
- [Docker firewall и UFW](https://docs.docker.com/engine/network/packet-filtering-firewalls/)
- [Laravel 13: production deployment](https://laravel.com/docs/13.x/deployment)
- [Laravel 13: queues](https://laravel.com/docs/13.x/queues)
- [Laravel 13: scheduler](https://laravel.com/docs/13.x/scheduling)
