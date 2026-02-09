# API Examples (Generated)

File ini di-generate dari `docs/openapi.json`.

Placeholder yang dipakai:
- `<domain>`: domain kamu (tanpa scheme).
- `<ADMIN_API_KEY>`: Admin API key per-user.
- `a.<aid>.<time>.<sha1>`: token admin (legacy).
- `c.<uid>.<time>.<sha1>`: token customer (legacy).

Catatan penting:
- Header `Authorization: Bearer <ADMIN_API_KEY>` hanya bekerja jika server meneruskan header Authorization ke PHP.
- CSRF di-bypass saat `isApi=true`, jadi `csrf_token` umumnya tidak perlu dikirim untuk API.

## Cheatsheet Auth

Admin API key (pilih salah satu):
```bash
curl -s -H "X-Admin-Api-Key: <ADMIN_API_KEY>" "https://<domain>/system/api.php?r=whoami/permissions"
curl -s -H "X-API-Key: <ADMIN_API_KEY>" "https://<domain>/system/api.php?r=whoami/permissions"
curl -s -H "Authorization: Bearer <ADMIN_API_KEY>" "https://<domain>/system/api.php?r=whoami/permissions"
```

Token (lebih aman via header):
```bash
curl -s -H "X-Token: a.<aid>.<time>.<sha1>" "https://<domain>/system/api.php?r=isValid"
curl -s "https://<domain>/system/api.php?r=isValid&token=a.<aid>.<time>.<sha1>"
```

## whoami

### GET /isValid
- Access: `public`
- Legacy route: `r=isValid`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (X-Token):
```bash
curl -s \
  -H "X-Token: <admin_or_customer_token>" \
  "https://<domain>/system/api.php/isValid"
```

Token (query):
```bash
curl -s "https://<domain>/system/api.php?r=isValid&token=<admin_or_customer_token>"
```

### GET /me
- Access: `public`
- Legacy route: `r=me`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/me"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=me&token=a.<aid>.<time>.<sha1>"
```

### GET /whoami
- Access: `mixed`
- Legacy route: `r=whoami`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/whoami"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/whoami"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/whoami"
```

Token (X-Token):
```bash
curl -s \
  -H "X-Token: <admin_or_customer_token>" \
  "https://<domain>/system/api.php/whoami"
```

Token (query):
```bash
curl -s "https://<domain>/system/api.php?r=whoami&token=<admin_or_customer_token>"
```

### GET /whoami/permissions
- Access: `mixed`
- Legacy route: `r=whoami/permissions`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/whoami/permissions"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/whoami/permissions"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/whoami/permissions"
```

Token (X-Token):
```bash
curl -s \
  -H "X-Token: <admin_or_customer_token>" \
  "https://<domain>/system/api.php/whoami/permissions"
```

Token (query):
```bash
curl -s "https://<domain>/system/api.php?r=whoami/permissions&token=<admin_or_customer_token>"
```

## accounts

### GET /accounts/change-password
- Access: `customer`
- Legacy route: `r=accounts/change-password`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/accounts/change-password"
```

Token (customer, query):
```bash
curl -s "https://<domain>/system/api.php?r=accounts/change-password&token=c.<uid>.<time>.<sha1>"
```

### POST /accounts/change-password-post
- Access: `customer`
- Legacy route: `r=accounts/change-password-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  -d "cnpass=<value>" \
  -d "npass=<value>" \
  -d "password=<value>" \
  "https://<domain>/system/api.php/accounts/change-password-post"
```

Token (customer, query):
```bash
curl -s \
  -X POST \
  -d "cnpass=<value>" \
  -d "npass=<value>" \
  -d "password=<value>" \
  "https://<domain>/system/api.php?r=accounts/change-password-post&token=c.<uid>.<time>.<sha1>"
```

### POST /accounts/edit-profile-post
- Access: `customer`
- Legacy route: `r=accounts/edit-profile-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  -d "address=<value>" \
  -d "email=<value>" \
  -d "faceDetect=<value>" \
  -d "fullname=<value>" \
  -d "phonenumber=<value>" \
  "https://<domain>/system/api.php/accounts/edit-profile-post"
```

Token (customer, query):
```bash
curl -s \
  -X POST \
  -d "address=<value>" \
  -d "email=<value>" \
  -d "faceDetect=<value>" \
  -d "fullname=<value>" \
  -d "phonenumber=<value>" \
  "https://<domain>/system/api.php?r=accounts/edit-profile-post&token=c.<uid>.<time>.<sha1>"
```

### GET /accounts/email-update
- Access: `customer`
- Legacy route: `r=accounts/email-update`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/accounts/email-update"
```

Token (customer, query):
```bash
curl -s "https://<domain>/system/api.php?r=accounts/email-update&token=c.<uid>.<time>.<sha1>"
```

### POST /accounts/email-update-otp
- Access: `customer`
- Legacy route: `r=accounts/email-update-otp`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  -d "email=<value>" \
  "https://<domain>/system/api.php/accounts/email-update-otp"
```

Token (customer, query):
```bash
curl -s \
  -X POST \
  -d "email=<value>" \
  "https://<domain>/system/api.php?r=accounts/email-update-otp&token=c.<uid>.<time>.<sha1>"
```

### POST /accounts/email-update-post
- Access: `customer`
- Legacy route: `r=accounts/email-update-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  -d "email=<value>" \
  -d "otp=<value>" \
  "https://<domain>/system/api.php/accounts/email-update-post"
```

Token (customer, query):
```bash
curl -s \
  -X POST \
  -d "email=<value>" \
  -d "otp=<value>" \
  "https://<domain>/system/api.php?r=accounts/email-update-post&token=c.<uid>.<time>.<sha1>"
```

### GET /accounts/language-update-post
- Access: `customer`
- Legacy route: `r=accounts/language-update-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `lang`

Token (customer, X-Token):
```bash
curl -s \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/accounts/language-update-post?lang=<value>"
```

Token (customer, query):
```bash
curl -s "https://<domain>/system/api.php?r=accounts/language-update-post&lang=<value>&token=c.<uid>.<time>.<sha1>"
```

### GET /accounts/phone-update
- Access: `customer`
- Legacy route: `r=accounts/phone-update`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/accounts/phone-update"
```

Token (customer, query):
```bash
curl -s "https://<domain>/system/api.php?r=accounts/phone-update&token=c.<uid>.<time>.<sha1>"
```

### POST /accounts/phone-update-otp
- Access: `customer`
- Legacy route: `r=accounts/phone-update-otp`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  -d "phone=<value>" \
  "https://<domain>/system/api.php/accounts/phone-update-otp"
```

Token (customer, query):
```bash
curl -s \
  -X POST \
  -d "phone=<value>" \
  "https://<domain>/system/api.php?r=accounts/phone-update-otp&token=c.<uid>.<time>.<sha1>"
```

### POST /accounts/phone-update-post
- Access: `customer`
- Legacy route: `r=accounts/phone-update-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  -d "otp=<value>" \
  -d "phone=<value>" \
  "https://<domain>/system/api.php/accounts/phone-update-post"
```

Token (customer, query):
```bash
curl -s \
  -X POST \
  -d "otp=<value>" \
  -d "phone=<value>" \
  "https://<domain>/system/api.php?r=accounts/phone-update-post&token=c.<uid>.<time>.<sha1>"
```

### GET /accounts/profile
- Access: `customer`
- Legacy route: `r=accounts/profile`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/accounts/profile"
```

Token (customer, query):
```bash
curl -s "https://<domain>/system/api.php?r=accounts/profile&token=c.<uid>.<time>.<sha1>"
```

## admin

### POST /admin/post
- Access: `public`
- Legacy route: `r=admin/post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Public:
```bash
curl -s \
  -X POST \
  -d "cf-turnstile-response=<value>" \
  -d "password=<value>" \
  -d "username=<value>" \
  "https://<domain>/system/api.php/admin/post"
```

## autoload

### GET /autoload/balance/{p2}/{p3}
- Access: `admin`
- Legacy route: `r=autoload/balance/{p2}/{p3}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/autoload/balance/<p2>/<p3>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/autoload/balance/<p2>/<p3>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/autoload/balance/<p2>/<p3>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/autoload/balance/<p2>/<p3>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=autoload/balance/<p2>/<p3>&token=a.<aid>.<time>.<sha1>"
```

### GET /autoload/bw_name/{p2}
- Access: `admin`
- Legacy route: `r=autoload/bw_name/{p2}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/autoload/bw_name/<p2>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/autoload/bw_name/<p2>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/autoload/bw_name/<p2>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/autoload/bw_name/<p2>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=autoload/bw_name/<p2>&token=a.<aid>.<time>.<sha1>"
```

### GET /autoload/customer_is_active/{p2}/{p3}
- Access: `admin`
- Legacy route: `r=autoload/customer_is_active/{p2}/{p3}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/autoload/customer_is_active/<p2>/<p3>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/autoload/customer_is_active/<p2>/<p3>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/autoload/customer_is_active/<p2>/<p3>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/autoload/customer_is_active/<p2>/<p3>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=autoload/customer_is_active/<p2>/<p3>&token=a.<aid>.<time>.<sha1>"
```

### GET /autoload/customer_select2
- Access: `admin`
- Legacy route: `r=autoload/customer_select2`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `s`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/autoload/customer_select2?s=<value>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/autoload/customer_select2?s=<value>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/autoload/customer_select2?s=<value>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/autoload/customer_select2?s=<value>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=autoload/customer_select2&s=<value>&token=a.<aid>.<time>.<sha1>"
```

### POST /autoload/plan
- Access: `admin`
- Legacy route: `r=autoload/plan`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "jenis=<value>" \
  -d "server=<value>" \
  "https://<domain>/system/api.php/autoload/plan"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "jenis=<value>" \
  -d "server=<value>" \
  "https://<domain>/system/api.php/autoload/plan"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "jenis=<value>" \
  -d "server=<value>" \
  "https://<domain>/system/api.php/autoload/plan"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "jenis=<value>" \
  -d "server=<value>" \
  "https://<domain>/system/api.php/autoload/plan"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "jenis=<value>" \
  -d "server=<value>" \
  "https://<domain>/system/api.php?r=autoload/plan&token=a.<aid>.<time>.<sha1>"
```

### GET /autoload/plan_is_active/{p2}
- Access: `admin`
- Legacy route: `r=autoload/plan_is_active/{p2}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/autoload/plan_is_active/<p2>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/autoload/plan_is_active/<p2>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/autoload/plan_is_active/<p2>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/autoload/plan_is_active/<p2>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=autoload/plan_is_active/<p2>&token=a.<aid>.<time>.<sha1>"
```

### GET /autoload/pool
- Access: `admin`
- Legacy route: `r=autoload/pool`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `routers`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/autoload/pool?routers=<value>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/autoload/pool?routers=<value>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/autoload/pool?routers=<value>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/autoload/pool?routers=<value>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=autoload/pool&routers=<value>&token=a.<aid>.<time>.<sha1>"
```

### GET /autoload/pppoe_ip_used
- Access: `admin`
- Legacy route: `r=autoload/pppoe_ip_used`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `id`, `ip`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/autoload/pppoe_ip_used?id=1&ip=<value>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/autoload/pppoe_ip_used?id=1&ip=<value>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/autoload/pppoe_ip_used?id=1&ip=<value>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/autoload/pppoe_ip_used?id=1&ip=<value>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=autoload/pppoe_ip_used&id=1&ip=<value>&token=a.<aid>.<time>.<sha1>"
```

### GET /autoload/pppoe_username_used
- Access: `admin`
- Legacy route: `r=autoload/pppoe_username_used`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `id`, `u`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/autoload/pppoe_username_used?id=1&u=<value>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/autoload/pppoe_username_used?id=1&u=<value>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/autoload/pppoe_username_used?id=1&u=<value>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/autoload/pppoe_username_used?id=1&u=<value>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=autoload/pppoe_username_used&id=1&u=<value>&token=a.<aid>.<time>.<sha1>"
```

### GET /autoload/server
- Access: `admin`
- Legacy route: `r=autoload/server`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/autoload/server"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/autoload/server"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/autoload/server"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/autoload/server"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=autoload/server&token=a.<aid>.<time>.<sha1>"
```

## autoload_user

### GET /autoload_user/bw_name/{p2}
- Access: `customer`
- Legacy route: `r=autoload_user/bw_name/{p2}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/autoload_user/bw_name/<p2>"
```

Token (customer, query):
```bash
curl -s "https://<domain>/system/api.php?r=autoload_user/bw_name/<p2>&token=c.<uid>.<time>.<sha1>"
```

### GET /autoload_user/inbox
- Access: `customer`
- Legacy route: `r=autoload_user/inbox`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/autoload_user/inbox"
```

Token (customer, query):
```bash
curl -s "https://<domain>/system/api.php?r=autoload_user/inbox&token=c.<uid>.<time>.<sha1>"
```

### GET /autoload_user/inbox_unread
- Access: `customer`
- Legacy route: `r=autoload_user/inbox_unread`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/autoload_user/inbox_unread"
```

Token (customer, query):
```bash
curl -s "https://<domain>/system/api.php?r=autoload_user/inbox_unread&token=c.<uid>.<time>.<sha1>"
```

### GET /autoload_user/isLogin/{p2}
- Access: `customer`
- Legacy route: `r=autoload_user/isLogin/{p2}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/autoload_user/isLogin/<p2>"
```

Token (customer, query):
```bash
curl -s "https://<domain>/system/api.php?r=autoload_user/isLogin/<p2>&token=c.<uid>.<time>.<sha1>"
```

### GET /autoload_user/language
- Access: `customer`
- Legacy route: `r=autoload_user/language`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `select`

Token (customer, X-Token):
```bash
curl -s \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/autoload_user/language?select=<value>"
```

Token (customer, query):
```bash
curl -s "https://<domain>/system/api.php?r=autoload_user/language&select=<value>&token=c.<uid>.<time>.<sha1>"
```

## bandwidth

### GET /bandwidth/add
- Access: `admin`
- Legacy route: `r=bandwidth/add`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Note: ini endpoint form. Create/update dilakukan via `POST /bandwidth/add-post`.

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/bandwidth/add"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/bandwidth/add"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/bandwidth/add"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/bandwidth/add"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=bandwidth/add&token=a.<aid>.<time>.<sha1>"
```

### POST /bandwidth/add-post
- Access: `admin`
- Legacy route: `r=bandwidth/add-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "name=<value>" \
  -d "rate_down=<value>" \
  -d "rate_down_unit=<value>" \
  -d "rate_up=<value>" \
  -d "rate_up_unit=<value>" \
  "https://<domain>/system/api.php/bandwidth/add-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "name=<value>" \
  -d "rate_down=<value>" \
  -d "rate_down_unit=<value>" \
  -d "rate_up=<value>" \
  -d "rate_up_unit=<value>" \
  "https://<domain>/system/api.php/bandwidth/add-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "name=<value>" \
  -d "rate_down=<value>" \
  -d "rate_down_unit=<value>" \
  -d "rate_up=<value>" \
  -d "rate_up_unit=<value>" \
  "https://<domain>/system/api.php/bandwidth/add-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "name=<value>" \
  -d "rate_down=<value>" \
  -d "rate_down_unit=<value>" \
  -d "rate_up=<value>" \
  -d "rate_up_unit=<value>" \
  "https://<domain>/system/api.php/bandwidth/add-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "name=<value>" \
  -d "rate_down=<value>" \
  -d "rate_down_unit=<value>" \
  -d "rate_up=<value>" \
  -d "rate_up_unit=<value>" \
  "https://<domain>/system/api.php?r=bandwidth/add-post&token=a.<aid>.<time>.<sha1>"
```

### GET /bandwidth/delete/{id}
- Access: `admin`
- Legacy route: `r=bandwidth/delete/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/bandwidth/delete/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/bandwidth/delete/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/bandwidth/delete/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/bandwidth/delete/<id>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=bandwidth/delete/<id>&token=a.<aid>.<time>.<sha1>"
```

### POST /bandwidth/edit-post
- Access: `admin`
- Legacy route: `r=bandwidth/edit-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "id=<value>" \
  -d "name=<value>" \
  -d "rate_down=<value>" \
  -d "rate_down_unit=<value>" \
  -d "rate_up=<value>" \
  -d "rate_up_unit=<value>" \
  "https://<domain>/system/api.php/bandwidth/edit-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "id=<value>" \
  -d "name=<value>" \
  -d "rate_down=<value>" \
  -d "rate_down_unit=<value>" \
  -d "rate_up=<value>" \
  -d "rate_up_unit=<value>" \
  "https://<domain>/system/api.php/bandwidth/edit-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "id=<value>" \
  -d "name=<value>" \
  -d "rate_down=<value>" \
  -d "rate_down_unit=<value>" \
  -d "rate_up=<value>" \
  -d "rate_up_unit=<value>" \
  "https://<domain>/system/api.php/bandwidth/edit-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "id=<value>" \
  -d "name=<value>" \
  -d "rate_down=<value>" \
  -d "rate_down_unit=<value>" \
  -d "rate_up=<value>" \
  -d "rate_up_unit=<value>" \
  "https://<domain>/system/api.php/bandwidth/edit-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "id=<value>" \
  -d "name=<value>" \
  -d "rate_down=<value>" \
  -d "rate_down_unit=<value>" \
  -d "rate_up=<value>" \
  -d "rate_up_unit=<value>" \
  "https://<domain>/system/api.php?r=bandwidth/edit-post&token=a.<aid>.<time>.<sha1>"
```

### GET /bandwidth/edit/{id}
- Access: `admin`
- Legacy route: `r=bandwidth/edit/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/bandwidth/edit/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/bandwidth/edit/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/bandwidth/edit/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/bandwidth/edit/<id>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=bandwidth/edit/<id>&token=a.<aid>.<time>.<sha1>"
```

### GET /bandwidth/list
- Access: `admin`
- Legacy route: `r=bandwidth/list`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/bandwidth/list?p=1"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/bandwidth/list?p=1"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/bandwidth/list?p=1"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/bandwidth/list?p=1"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=bandwidth/list&p=1&token=a.<aid>.<time>.<sha1>"
```

### POST /bandwidth/list
- Access: `admin`
- Legacy route: `r=bandwidth/list`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/bandwidth/list?p=1"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/bandwidth/list?p=1"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/bandwidth/list?p=1"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/bandwidth/list?p=1"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "name=<value>" \
  "https://<domain>/system/api.php?r=bandwidth/list&p=1&token=a.<aid>.<time>.<sha1>"
```

## callback

### GET /callback/{gateway}
- Access: `public`
- Legacy route: `r=callback/{gateway}`
- Response: non-JSON (binary/csv/pdf/html/text)

Public:
```bash
curl -s "https://<domain>/system/api.php/callback/<gateway>"
```

### POST /callback/{gateway}
- Access: `public`
- Legacy route: `r=callback/{gateway}`
- Response: non-JSON (binary/csv/pdf/html/text)

Public:
```bash
curl -s \
  -X POST \
  "https://<domain>/system/api.php/callback/<gateway>"
```

## community

### GET /community/rollback
- Access: `admin`
- Legacy route: `r=community/rollback`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/community/rollback"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/community/rollback"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/community/rollback"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/community/rollback"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=community/rollback&token=a.<aid>.<time>.<sha1>"
```

## coupons

### GET /coupons
- Access: `admin`
- Legacy route: `r=coupons`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/coupons?p=1"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/coupons?p=1"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/coupons?p=1"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/coupons?p=1"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=coupons&p=1&token=a.<aid>.<time>.<sha1>"
```

### POST /coupons
- Access: `admin`
- Legacy route: `r=coupons`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "filter=<value>" \
  -d "search=<value>" \
  "https://<domain>/system/api.php/coupons?p=1"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "filter=<value>" \
  -d "search=<value>" \
  "https://<domain>/system/api.php/coupons?p=1"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "filter=<value>" \
  -d "search=<value>" \
  "https://<domain>/system/api.php/coupons?p=1"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "filter=<value>" \
  -d "search=<value>" \
  "https://<domain>/system/api.php/coupons?p=1"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "filter=<value>" \
  -d "search=<value>" \
  "https://<domain>/system/api.php?r=coupons&p=1&token=a.<aid>.<time>.<sha1>"
```

### GET /coupons/add
- Access: `admin`
- Legacy route: `r=coupons/add`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Note: ini endpoint form. Create/update dilakukan via `POST /coupons/add-post`.

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/coupons/add"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/coupons/add"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/coupons/add"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/coupons/add"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=coupons/add&token=a.<aid>.<time>.<sha1>"
```

### POST /coupons/add-post
- Access: `admin`
- Legacy route: `r=coupons/add-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "code=<value>" \
  -d "description=<value>" \
  -d "end_date=<value>" \
  -d "max_discount_amount=<value>" \
  -d "max_usage=<value>" \
  -d "min_order_amount=<value>" \
  -d "start_date=<value>" \
  -d "status=<value>" \
  -d "type=<value>" \
  -d "value=<value>" \
  "https://<domain>/system/api.php/coupons/add-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "code=<value>" \
  -d "description=<value>" \
  -d "end_date=<value>" \
  -d "max_discount_amount=<value>" \
  -d "max_usage=<value>" \
  -d "min_order_amount=<value>" \
  -d "start_date=<value>" \
  -d "status=<value>" \
  -d "type=<value>" \
  -d "value=<value>" \
  "https://<domain>/system/api.php/coupons/add-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "code=<value>" \
  -d "description=<value>" \
  -d "end_date=<value>" \
  -d "max_discount_amount=<value>" \
  -d "max_usage=<value>" \
  -d "min_order_amount=<value>" \
  -d "start_date=<value>" \
  -d "status=<value>" \
  -d "type=<value>" \
  -d "value=<value>" \
  "https://<domain>/system/api.php/coupons/add-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "code=<value>" \
  -d "description=<value>" \
  -d "end_date=<value>" \
  -d "max_discount_amount=<value>" \
  -d "max_usage=<value>" \
  -d "min_order_amount=<value>" \
  -d "start_date=<value>" \
  -d "status=<value>" \
  -d "type=<value>" \
  -d "value=<value>" \
  "https://<domain>/system/api.php/coupons/add-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "code=<value>" \
  -d "description=<value>" \
  -d "end_date=<value>" \
  -d "max_discount_amount=<value>" \
  -d "max_usage=<value>" \
  -d "min_order_amount=<value>" \
  -d "start_date=<value>" \
  -d "status=<value>" \
  -d "type=<value>" \
  -d "value=<value>" \
  "https://<domain>/system/api.php?r=coupons/add-post&token=a.<aid>.<time>.<sha1>"
```

### POST /coupons/delete
- Access: `admin`
- Legacy route: `r=coupons/delete`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "couponIds=<value>" \
  "https://<domain>/system/api.php/coupons/delete"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "couponIds=<value>" \
  "https://<domain>/system/api.php/coupons/delete"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "couponIds=<value>" \
  "https://<domain>/system/api.php/coupons/delete"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "couponIds=<value>" \
  "https://<domain>/system/api.php/coupons/delete"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "couponIds=<value>" \
  "https://<domain>/system/api.php?r=coupons/delete&token=a.<aid>.<time>.<sha1>"
```

### POST /coupons/edit-post
- Access: `admin`
- Legacy route: `r=coupons/edit-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "code=<value>" \
  -d "description=<value>" \
  -d "end_date=<value>" \
  -d "max_discount_amount=<value>" \
  -d "max_usage=<value>" \
  -d "min_order_amount=<value>" \
  -d "start_date=<value>" \
  -d "status=<value>" \
  -d "type=<value>" \
  -d "value=<value>" \
  "https://<domain>/system/api.php/coupons/edit-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "code=<value>" \
  -d "description=<value>" \
  -d "end_date=<value>" \
  -d "max_discount_amount=<value>" \
  -d "max_usage=<value>" \
  -d "min_order_amount=<value>" \
  -d "start_date=<value>" \
  -d "status=<value>" \
  -d "type=<value>" \
  -d "value=<value>" \
  "https://<domain>/system/api.php/coupons/edit-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "code=<value>" \
  -d "description=<value>" \
  -d "end_date=<value>" \
  -d "max_discount_amount=<value>" \
  -d "max_usage=<value>" \
  -d "min_order_amount=<value>" \
  -d "start_date=<value>" \
  -d "status=<value>" \
  -d "type=<value>" \
  -d "value=<value>" \
  "https://<domain>/system/api.php/coupons/edit-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "code=<value>" \
  -d "description=<value>" \
  -d "end_date=<value>" \
  -d "max_discount_amount=<value>" \
  -d "max_usage=<value>" \
  -d "min_order_amount=<value>" \
  -d "start_date=<value>" \
  -d "status=<value>" \
  -d "type=<value>" \
  -d "value=<value>" \
  "https://<domain>/system/api.php/coupons/edit-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "code=<value>" \
  -d "description=<value>" \
  -d "end_date=<value>" \
  -d "max_discount_amount=<value>" \
  -d "max_usage=<value>" \
  -d "min_order_amount=<value>" \
  -d "start_date=<value>" \
  -d "status=<value>" \
  -d "type=<value>" \
  -d "value=<value>" \
  "https://<domain>/system/api.php?r=coupons/edit-post&token=a.<aid>.<time>.<sha1>"
```

### POST /coupons/edit/{coupon_id}
- Access: `admin`
- Legacy route: `r=coupons/edit/{coupon_id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/coupons/edit/<coupon_id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/coupons/edit/<coupon_id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/coupons/edit/<coupon_id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/coupons/edit/<coupon_id>"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  "https://<domain>/system/api.php?r=coupons/edit/<coupon_id>&token=a.<aid>.<time>.<sha1>"
```

### POST /coupons/status
- Access: `admin`
- Legacy route: `r=coupons/status`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "coupon_id=<value>" \
  -d "status=<value>" \
  "https://<domain>/system/api.php/coupons/status"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "coupon_id=<value>" \
  -d "status=<value>" \
  "https://<domain>/system/api.php/coupons/status"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "coupon_id=<value>" \
  -d "status=<value>" \
  "https://<domain>/system/api.php/coupons/status"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "coupon_id=<value>" \
  -d "status=<value>" \
  "https://<domain>/system/api.php/coupons/status"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "coupon_id=<value>" \
  -d "status=<value>" \
  "https://<domain>/system/api.php?r=coupons/status&token=a.<aid>.<time>.<sha1>"
```

## csrf-refresh

### GET /csrf-refresh
- Access: `mixed`
- Legacy route: `r=csrf-refresh`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/csrf-refresh"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/csrf-refresh"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/csrf-refresh"
```

Token (X-Token):
```bash
curl -s \
  -H "X-Token: <admin_or_customer_token>" \
  "https://<domain>/system/api.php/csrf-refresh"
```

Token (query):
```bash
curl -s "https://<domain>/system/api.php?r=csrf-refresh&token=<admin_or_customer_token>"
```

## customers

### GET /customers
- Access: `admin`
- Legacy route: `r=customers`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`, `filter`, `search`, `order`, `orderby`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers?p=1&filter=Active&search=<query>&order=username&orderby=asc"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers?p=1&filter=Active&search=<query>&order=username&orderby=asc"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers?p=1&filter=Active&search=<query>&order=username&orderby=asc"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/customers?p=1&filter=Active&search=<query>&order=username&orderby=asc"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=customers&p=1&filter=Active&search=<query>&order=username&orderby=asc&token=a.<aid>.<time>.<sha1>"
```

### GET /customers/add
- Access: `admin`
- Legacy route: `r=customers/add`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Note: ini endpoint form. Create/update dilakukan via `POST /customers/add-post`.

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/add"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/add"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/add"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/customers/add"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=customers/add&token=a.<aid>.<time>.<sha1>"
```

### POST /customers/add-post
- Access: `admin`
- Legacy route: `r=customers/add-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "account_type=<value>" \
  -d "address=<value>" \
  -d "city=<value>" \
  -d "coordinates=<value>" \
  -d "district=<value>" \
  -d "email=<value>" \
  -d "fullname=<value>" \
  -d "password=<value>" \
  -d "phonenumber=<value>" \
  -d "pppoe_ip=<value>" \
  -d "pppoe_password=<value>" \
  -d "pppoe_username=<value>" \
  -d "service_type=<value>" \
  -d "state=<value>" \
  -d "username=<value>" \
  -d "zip=<value>" \
  "https://<domain>/system/api.php/customers/add-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "account_type=<value>" \
  -d "address=<value>" \
  -d "city=<value>" \
  -d "coordinates=<value>" \
  -d "district=<value>" \
  -d "email=<value>" \
  -d "fullname=<value>" \
  -d "password=<value>" \
  -d "phonenumber=<value>" \
  -d "pppoe_ip=<value>" \
  -d "pppoe_password=<value>" \
  -d "pppoe_username=<value>" \
  -d "service_type=<value>" \
  -d "state=<value>" \
  -d "username=<value>" \
  -d "zip=<value>" \
  "https://<domain>/system/api.php/customers/add-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "account_type=<value>" \
  -d "address=<value>" \
  -d "city=<value>" \
  -d "coordinates=<value>" \
  -d "district=<value>" \
  -d "email=<value>" \
  -d "fullname=<value>" \
  -d "password=<value>" \
  -d "phonenumber=<value>" \
  -d "pppoe_ip=<value>" \
  -d "pppoe_password=<value>" \
  -d "pppoe_username=<value>" \
  -d "service_type=<value>" \
  -d "state=<value>" \
  -d "username=<value>" \
  -d "zip=<value>" \
  "https://<domain>/system/api.php/customers/add-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "account_type=<value>" \
  -d "address=<value>" \
  -d "city=<value>" \
  -d "coordinates=<value>" \
  -d "district=<value>" \
  -d "email=<value>" \
  -d "fullname=<value>" \
  -d "password=<value>" \
  -d "phonenumber=<value>" \
  -d "pppoe_ip=<value>" \
  -d "pppoe_password=<value>" \
  -d "pppoe_username=<value>" \
  -d "service_type=<value>" \
  -d "state=<value>" \
  -d "username=<value>" \
  -d "zip=<value>" \
  "https://<domain>/system/api.php/customers/add-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "account_type=<value>" \
  -d "address=<value>" \
  -d "city=<value>" \
  -d "coordinates=<value>" \
  -d "district=<value>" \
  -d "email=<value>" \
  -d "fullname=<value>" \
  -d "password=<value>" \
  -d "phonenumber=<value>" \
  -d "pppoe_ip=<value>" \
  -d "pppoe_password=<value>" \
  -d "pppoe_username=<value>" \
  -d "service_type=<value>" \
  -d "state=<value>" \
  -d "username=<value>" \
  -d "zip=<value>" \
  "https://<domain>/system/api.php?r=customers/add-post&token=a.<aid>.<time>.<sha1>"
```

### POST /customers/csv
- Access: `admin`
- Legacy route: `r=customers/csv`
- Response: non-JSON (binary/csv/pdf/html/text)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/csv"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/csv"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/csv"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/customers/csv"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  "https://<domain>/system/api.php?r=customers/csv&token=a.<aid>.<time>.<sha1>"
```

### GET /customers/csv-prepaid
- Access: `admin`
- Legacy route: `r=customers/csv-prepaid`
- Response: non-JSON (binary/csv/pdf/html/text)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/csv-prepaid"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/csv-prepaid"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/csv-prepaid"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/customers/csv-prepaid"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=customers/csv-prepaid&token=a.<aid>.<time>.<sha1>"
```

### POST /customers/deactivate/{id_customer}/{plan_id}
- Access: `admin`
- Legacy route: `r=customers/deactivate/{id_customer}/{plan_id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/deactivate/<id_customer>/<plan_id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/deactivate/<id_customer>/<plan_id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/deactivate/<id_customer>/<plan_id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/customers/deactivate/<id_customer>/<plan_id>"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  "https://<domain>/system/api.php?r=customers/deactivate/<id_customer>/<plan_id>&token=a.<aid>.<time>.<sha1>"
```

### POST /customers/delete/{id}
- Access: `admin`
- Legacy route: `r=customers/delete/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/delete/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/delete/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/delete/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/customers/delete/<id>"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  "https://<domain>/system/api.php?r=customers/delete/<id>&token=a.<aid>.<time>.<sha1>"
```

### POST /customers/edit-post
- Access: `admin`
- Legacy route: `r=customers/edit-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "account_type=<value>" \
  -d "address=<value>" \
  -d "city=<value>" \
  -d "coordinates=<value>" \
  -d "district=<value>" \
  -d "email=<value>" \
  -d "export=<value>" \
  -d "faceDetect=<value>" \
  -d "fullname=<value>" \
  -d "id=<value>" \
  -d "password=<value>" \
  -d "phonenumber=<value>" \
  -d "pppoe_ip=<value>" \
  -d "pppoe_password=<value>" \
  -d "pppoe_username=<value>" \
  -d "service_type=<value>" \
  -d "state=<value>" \
  -d "status=<value>" \
  -d "username=<value>" \
  -d "zip=<value>" \
  "https://<domain>/system/api.php/customers/edit-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "account_type=<value>" \
  -d "address=<value>" \
  -d "city=<value>" \
  -d "coordinates=<value>" \
  -d "district=<value>" \
  -d "email=<value>" \
  -d "export=<value>" \
  -d "faceDetect=<value>" \
  -d "fullname=<value>" \
  -d "id=<value>" \
  -d "password=<value>" \
  -d "phonenumber=<value>" \
  -d "pppoe_ip=<value>" \
  -d "pppoe_password=<value>" \
  -d "pppoe_username=<value>" \
  -d "service_type=<value>" \
  -d "state=<value>" \
  -d "status=<value>" \
  -d "username=<value>" \
  -d "zip=<value>" \
  "https://<domain>/system/api.php/customers/edit-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "account_type=<value>" \
  -d "address=<value>" \
  -d "city=<value>" \
  -d "coordinates=<value>" \
  -d "district=<value>" \
  -d "email=<value>" \
  -d "export=<value>" \
  -d "faceDetect=<value>" \
  -d "fullname=<value>" \
  -d "id=<value>" \
  -d "password=<value>" \
  -d "phonenumber=<value>" \
  -d "pppoe_ip=<value>" \
  -d "pppoe_password=<value>" \
  -d "pppoe_username=<value>" \
  -d "service_type=<value>" \
  -d "state=<value>" \
  -d "status=<value>" \
  -d "username=<value>" \
  -d "zip=<value>" \
  "https://<domain>/system/api.php/customers/edit-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "account_type=<value>" \
  -d "address=<value>" \
  -d "city=<value>" \
  -d "coordinates=<value>" \
  -d "district=<value>" \
  -d "email=<value>" \
  -d "export=<value>" \
  -d "faceDetect=<value>" \
  -d "fullname=<value>" \
  -d "id=<value>" \
  -d "password=<value>" \
  -d "phonenumber=<value>" \
  -d "pppoe_ip=<value>" \
  -d "pppoe_password=<value>" \
  -d "pppoe_username=<value>" \
  -d "service_type=<value>" \
  -d "state=<value>" \
  -d "status=<value>" \
  -d "username=<value>" \
  -d "zip=<value>" \
  "https://<domain>/system/api.php/customers/edit-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "account_type=<value>" \
  -d "address=<value>" \
  -d "city=<value>" \
  -d "coordinates=<value>" \
  -d "district=<value>" \
  -d "email=<value>" \
  -d "export=<value>" \
  -d "faceDetect=<value>" \
  -d "fullname=<value>" \
  -d "id=<value>" \
  -d "password=<value>" \
  -d "phonenumber=<value>" \
  -d "pppoe_ip=<value>" \
  -d "pppoe_password=<value>" \
  -d "pppoe_username=<value>" \
  -d "service_type=<value>" \
  -d "state=<value>" \
  -d "status=<value>" \
  -d "username=<value>" \
  -d "zip=<value>" \
  "https://<domain>/system/api.php?r=customers/edit-post&token=a.<aid>.<time>.<sha1>"
```

### POST /customers/edit/{id}/{p3}
- Access: `admin`
- Legacy route: `r=customers/edit/{id}/{p3}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/edit/<id>/<p3>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/edit/<id>/<p3>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/edit/<id>/<p3>"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/customers/edit/<id>/<p3>"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  "https://<domain>/system/api.php?r=customers/edit/<id>/<p3>&token=a.<aid>.<time>.<sha1>"
```

### POST /customers/login/{id}
- Access: `admin`
- Legacy route: `r=customers/login/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/login/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/login/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/login/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/customers/login/<id>"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  "https://<domain>/system/api.php?r=customers/login/<id>&token=a.<aid>.<time>.<sha1>"
```

### POST /customers/recharge/{id_customer}/{plan_id}
- Access: `admin`
- Legacy route: `r=customers/recharge/{id_customer}/{plan_id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/recharge/<id_customer>/<plan_id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/recharge/<id_customer>/<plan_id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/recharge/<id_customer>/<plan_id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/customers/recharge/<id_customer>/<plan_id>"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  "https://<domain>/system/api.php?r=customers/recharge/<id_customer>/<plan_id>&token=a.<aid>.<time>.<sha1>"
```

### POST /customers/sync/{id_customer}
- Access: `admin`
- Legacy route: `r=customers/sync/{id_customer}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/sync/<id_customer>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/sync/<id_customer>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/sync/<id_customer>"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/customers/sync/<id_customer>"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  "https://<domain>/system/api.php?r=customers/sync/<id_customer>&token=a.<aid>.<time>.<sha1>"
```

### GET /customers/view/{id}/{v}
- Access: `admin`
- Legacy route: `r=customers/view/{id}/{v}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/view/<id>/order"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/view/<id>/order"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/view/<id>/order"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/customers/view/<id>/order"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=customers/view/<id>/order&token=a.<aid>.<time>.<sha1>"
```

### GET /customers/viewu/{p2}
- Access: `admin`
- Legacy route: `r=customers/viewu/{p2}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/viewu/<p2>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/viewu/<p2>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/customers/viewu/<p2>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/customers/viewu/<p2>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=customers/viewu/<p2>&token=a.<aid>.<time>.<sha1>"
```

## customfield

### POST /customfield/save
- Access: `admin`
- Legacy route: `r=customfield/save`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "name[]=<value>" \
  -d "order[]=<value>" \
  -d "placeholder[]=<value>" \
  -d "register[]=<value>" \
  -d "required[]=<value>" \
  -d "type[]=<value>" \
  -d "value[]=<value>" \
  "https://<domain>/system/api.php/customfield/save"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "name[]=<value>" \
  -d "order[]=<value>" \
  -d "placeholder[]=<value>" \
  -d "register[]=<value>" \
  -d "required[]=<value>" \
  -d "type[]=<value>" \
  -d "value[]=<value>" \
  "https://<domain>/system/api.php/customfield/save"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "name[]=<value>" \
  -d "order[]=<value>" \
  -d "placeholder[]=<value>" \
  -d "register[]=<value>" \
  -d "required[]=<value>" \
  -d "type[]=<value>" \
  -d "value[]=<value>" \
  "https://<domain>/system/api.php/customfield/save"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "name[]=<value>" \
  -d "order[]=<value>" \
  -d "placeholder[]=<value>" \
  -d "register[]=<value>" \
  -d "required[]=<value>" \
  -d "type[]=<value>" \
  -d "value[]=<value>" \
  "https://<domain>/system/api.php/customfield/save"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "name[]=<value>" \
  -d "order[]=<value>" \
  -d "placeholder[]=<value>" \
  -d "register[]=<value>" \
  -d "required[]=<value>" \
  -d "type[]=<value>" \
  -d "value[]=<value>" \
  "https://<domain>/system/api.php?r=customfield/save&token=a.<aid>.<time>.<sha1>"
```

## dashboard

### GET /dashboard
- Access: `admin`
- Legacy route: `r=dashboard`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/dashboard"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/dashboard"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/dashboard"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/dashboard"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=dashboard&token=a.<aid>.<time>.<sha1>"
```

## default

### GET /default
- Access: `public`
- Legacy route: `r=default`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Public:
```bash
curl -s "https://<domain>/system/api.php/default"
```

## export

### GET /export/pdf-by-date
- Access: `admin`
- Legacy route: `r=export/pdf-by-date`
- Response: non-JSON (binary/csv/pdf/html/text)
- Query params: `ed`, `sd`, `te`, `ts`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/export/pdf-by-date?ed=2026-01-31&sd=2026-01-01&te=23:59&ts=00:00"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/export/pdf-by-date?ed=2026-01-31&sd=2026-01-01&te=23:59&ts=00:00"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/export/pdf-by-date?ed=2026-01-31&sd=2026-01-01&te=23:59&ts=00:00"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/export/pdf-by-date?ed=2026-01-31&sd=2026-01-01&te=23:59&ts=00:00"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=export/pdf-by-date&ed=2026-01-31&sd=2026-01-01&te=23:59&ts=00:00&token=a.<aid>.<time>.<sha1>"
```

### POST /export/pdf-by-period
- Access: `admin`
- Legacy route: `r=export/pdf-by-period`
- Response: non-JSON (binary/csv/pdf/html/text)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "fdate=<value>" \
  -d "stype=<value>" \
  -d "tdate=<value>" \
  "https://<domain>/system/api.php/export/pdf-by-period"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "fdate=<value>" \
  -d "stype=<value>" \
  -d "tdate=<value>" \
  "https://<domain>/system/api.php/export/pdf-by-period"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "fdate=<value>" \
  -d "stype=<value>" \
  -d "tdate=<value>" \
  "https://<domain>/system/api.php/export/pdf-by-period"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "fdate=<value>" \
  -d "stype=<value>" \
  -d "tdate=<value>" \
  "https://<domain>/system/api.php/export/pdf-by-period"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "fdate=<value>" \
  -d "stype=<value>" \
  -d "tdate=<value>" \
  "https://<domain>/system/api.php?r=export/pdf-by-period&token=a.<aid>.<time>.<sha1>"
```

### GET /export/print-by-date
- Access: `admin`
- Legacy route: `r=export/print-by-date`
- Response: non-JSON (binary/csv/pdf/html/text)
- Query params: `ed`, `sd`, `te`, `ts`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/export/print-by-date?ed=2026-01-31&sd=2026-01-01&te=23:59&ts=00:00"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/export/print-by-date?ed=2026-01-31&sd=2026-01-01&te=23:59&ts=00:00"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/export/print-by-date?ed=2026-01-31&sd=2026-01-01&te=23:59&ts=00:00"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/export/print-by-date?ed=2026-01-31&sd=2026-01-01&te=23:59&ts=00:00"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=export/print-by-date&ed=2026-01-31&sd=2026-01-01&te=23:59&ts=00:00&token=a.<aid>.<time>.<sha1>"
```

### POST /export/print-by-period
- Access: `admin`
- Legacy route: `r=export/print-by-period`
- Response: non-JSON (binary/csv/pdf/html/text)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "fdate=<value>" \
  -d "stype=<value>" \
  -d "tdate=<value>" \
  "https://<domain>/system/api.php/export/print-by-period"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "fdate=<value>" \
  -d "stype=<value>" \
  -d "tdate=<value>" \
  "https://<domain>/system/api.php/export/print-by-period"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "fdate=<value>" \
  -d "stype=<value>" \
  -d "tdate=<value>" \
  "https://<domain>/system/api.php/export/print-by-period"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "fdate=<value>" \
  -d "stype=<value>" \
  -d "tdate=<value>" \
  "https://<domain>/system/api.php/export/print-by-period"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "fdate=<value>" \
  -d "stype=<value>" \
  -d "tdate=<value>" \
  "https://<domain>/system/api.php?r=export/print-by-period&token=a.<aid>.<time>.<sha1>"
```

## forgot

### GET /forgot
- Access: `public`
- Legacy route: `r=forgot`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `step`

Public:
```bash
curl -s "https://<domain>/system/api.php/forgot?step=<value>"
```

### POST /forgot
- Access: `public`
- Legacy route: `r=forgot`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `step`

Public:
```bash
curl -s \
  -X POST \
  "https://<domain>/system/api.php/forgot?step=<value>"
```

## home

### GET /home
- Access: `customer`
- Legacy route: `r=home`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/home"
```

Token (customer, query):
```bash
curl -s "https://<domain>/system/api.php?r=home&token=c.<uid>.<time>.<sha1>"
```

## invoices

### GET /invoices/list
- Access: `admin`
- Legacy route: `r=invoices/list`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/invoices/list"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/invoices/list"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/invoices/list"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/invoices/list"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=invoices/list&token=a.<aid>.<time>.<sha1>"
```

## login

### POST /login/activation
- Access: `public`
- Legacy route: `r=login/activation`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Public:
```bash
curl -s \
  -X POST \
  -d "username=<value>" \
  -d "voucher=<value>" \
  -d "voucher_only=<value>" \
  "https://<domain>/system/api.php/login/activation"
```

### POST /login/post
- Access: `public`
- Legacy route: `r=login/post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Public:
```bash
curl -s \
  -X POST \
  -d "cf-turnstile-response=<value>" \
  -d "password=<value>" \
  -d "username=<value>" \
  "https://<domain>/system/api.php/login/post"
```

## logout

### POST /logout
- Access: `public`
- Legacy route: `r=logout`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Public:
```bash
curl -s \
  -X POST \
  "https://<domain>/system/api.php/logout"
```

## logs

### GET /logs/list
- Access: `admin`
- Legacy route: `r=logs/list`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`, `q`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/logs/list?p=1&q=<query>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/logs/list?p=1&q=<query>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/logs/list?p=1&q=<query>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/logs/list?p=1&q=<query>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=logs/list&p=1&q=<query>&token=a.<aid>.<time>.<sha1>"
```

### POST /logs/list
- Access: `admin`
- Legacy route: `r=logs/list`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`, `q`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "keep=<value>" \
  -d "q=<value>" \
  "https://<domain>/system/api.php/logs/list?p=1&q=<query>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "keep=<value>" \
  -d "q=<value>" \
  "https://<domain>/system/api.php/logs/list?p=1&q=<query>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "keep=<value>" \
  -d "q=<value>" \
  "https://<domain>/system/api.php/logs/list?p=1&q=<query>"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "keep=<value>" \
  -d "q=<value>" \
  "https://<domain>/system/api.php/logs/list?p=1&q=<query>"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "keep=<value>" \
  -d "q=<value>" \
  "https://<domain>/system/api.php?r=logs/list&p=1&q=<query>&token=a.<aid>.<time>.<sha1>"
```

### GET /logs/list-csv
- Access: `admin`
- Legacy route: `r=logs/list-csv`
- Response: non-JSON (binary/csv/pdf/html/text)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/logs/list-csv"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/logs/list-csv"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/logs/list-csv"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/logs/list-csv"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=logs/list-csv&token=a.<aid>.<time>.<sha1>"
```

### GET /logs/message
- Access: `admin`
- Legacy route: `r=logs/message`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`, `q`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/logs/message?p=1&q=<query>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/logs/message?p=1&q=<query>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/logs/message?p=1&q=<query>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/logs/message?p=1&q=<query>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=logs/message&p=1&q=<query>&token=a.<aid>.<time>.<sha1>"
```

### POST /logs/message
- Access: `admin`
- Legacy route: `r=logs/message`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`, `q`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "keep=<value>" \
  -d "q=<value>" \
  "https://<domain>/system/api.php/logs/message?p=1&q=<query>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "keep=<value>" \
  -d "q=<value>" \
  "https://<domain>/system/api.php/logs/message?p=1&q=<query>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "keep=<value>" \
  -d "q=<value>" \
  "https://<domain>/system/api.php/logs/message?p=1&q=<query>"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "keep=<value>" \
  -d "q=<value>" \
  "https://<domain>/system/api.php/logs/message?p=1&q=<query>"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "keep=<value>" \
  -d "q=<value>" \
  "https://<domain>/system/api.php?r=logs/message&p=1&q=<query>&token=a.<aid>.<time>.<sha1>"
```

### GET /logs/message-csv
- Access: `admin`
- Legacy route: `r=logs/message-csv`
- Response: non-JSON (binary/csv/pdf/html/text)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/logs/message-csv"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/logs/message-csv"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/logs/message-csv"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/logs/message-csv"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=logs/message-csv&token=a.<aid>.<time>.<sha1>"
```

### GET /logs/radius
- Access: `admin`
- Legacy route: `r=logs/radius`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`, `q`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/logs/radius?p=1&q=<query>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/logs/radius?p=1&q=<query>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/logs/radius?p=1&q=<query>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/logs/radius?p=1&q=<query>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=logs/radius&p=1&q=<query>&token=a.<aid>.<time>.<sha1>"
```

### POST /logs/radius
- Access: `admin`
- Legacy route: `r=logs/radius`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`, `q`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "keep=<value>" \
  -d "q=<value>" \
  "https://<domain>/system/api.php/logs/radius?p=1&q=<query>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "keep=<value>" \
  -d "q=<value>" \
  "https://<domain>/system/api.php/logs/radius?p=1&q=<query>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "keep=<value>" \
  -d "q=<value>" \
  "https://<domain>/system/api.php/logs/radius?p=1&q=<query>"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "keep=<value>" \
  -d "q=<value>" \
  "https://<domain>/system/api.php/logs/radius?p=1&q=<query>"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "keep=<value>" \
  -d "q=<value>" \
  "https://<domain>/system/api.php?r=logs/radius&p=1&q=<query>&token=a.<aid>.<time>.<sha1>"
```

### GET /logs/radius-csv
- Access: `admin`
- Legacy route: `r=logs/radius-csv`
- Response: non-JSON (binary/csv/pdf/html/text)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/logs/radius-csv"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/logs/radius-csv"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/logs/radius-csv"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/logs/radius-csv"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=logs/radius-csv&token=a.<aid>.<time>.<sha1>"
```

## mail

### GET /mail
- Access: `customer`
- Legacy route: `r=mail`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`, `q`

Token (customer, X-Token):
```bash
curl -s \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/mail?p=1&q=<query>"
```

Token (customer, query):
```bash
curl -s "https://<domain>/system/api.php?r=mail&p=1&q=<query>&token=c.<uid>.<time>.<sha1>"
```

### GET /mail/delete/{p2}
- Access: `customer`
- Legacy route: `r=mail/delete/{p2}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`, `q`

Token (customer, X-Token):
```bash
curl -s \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/mail/delete/<p2>?p=1&q=<query>"
```

Token (customer, query):
```bash
curl -s "https://<domain>/system/api.php?r=mail/delete/<p2>&p=1&q=<query>&token=c.<uid>.<time>.<sha1>"
```

### GET /mail/view/{p2}
- Access: `customer`
- Legacy route: `r=mail/view/{p2}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/mail/view/<p2>"
```

Token (customer, query):
```bash
curl -s "https://<domain>/system/api.php?r=mail/view/<p2>&token=c.<uid>.<time>.<sha1>"
```

## maps

### GET /maps/customer
- Access: `admin`
- Legacy route: `r=maps/customer`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`, `search`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/maps/customer?p=1&search=<query>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/maps/customer?p=1&search=<query>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/maps/customer?p=1&search=<query>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/maps/customer?p=1&search=<query>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=maps/customer&p=1&search=<query>&token=a.<aid>.<time>.<sha1>"
```

### GET /maps/routers
- Access: `admin`
- Legacy route: `r=maps/routers`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/maps/routers?p=1"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/maps/routers?p=1"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/maps/routers?p=1"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/maps/routers?p=1"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=maps/routers&p=1&token=a.<aid>.<time>.<sha1>"
```

### POST /maps/routers
- Access: `admin`
- Legacy route: `r=maps/routers`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/maps/routers?p=1"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/maps/routers?p=1"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/maps/routers?p=1"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/maps/routers?p=1"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "name=<value>" \
  "https://<domain>/system/api.php?r=maps/routers&p=1&token=a.<aid>.<time>.<sha1>"
```

## message

### POST /message/resend-post
- Access: `admin`
- Legacy route: `r=message/resend-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "channel=<value>" \
  -d "log_id=<value>" \
  -d "message=<value>" \
  -d "recipient=<value>" \
  "https://<domain>/system/api.php/message/resend-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "channel=<value>" \
  -d "log_id=<value>" \
  -d "message=<value>" \
  -d "recipient=<value>" \
  "https://<domain>/system/api.php/message/resend-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "channel=<value>" \
  -d "log_id=<value>" \
  -d "message=<value>" \
  -d "recipient=<value>" \
  "https://<domain>/system/api.php/message/resend-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "channel=<value>" \
  -d "log_id=<value>" \
  -d "message=<value>" \
  -d "recipient=<value>" \
  "https://<domain>/system/api.php/message/resend-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "channel=<value>" \
  -d "log_id=<value>" \
  -d "message=<value>" \
  -d "recipient=<value>" \
  "https://<domain>/system/api.php?r=message/resend-post&token=a.<aid>.<time>.<sha1>"
```

### GET /message/resend/{logId}
- Access: `admin`
- Legacy route: `r=message/resend/{logId}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `id`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/message/resend/<logId>?id=1"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/message/resend/<logId>?id=1"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/message/resend/<logId>?id=1"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/message/resend/<logId>?id=1"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=message/resend/<logId>&id=1&token=a.<aid>.<time>.<sha1>"
```

### POST /message/send-post
- Access: `admin`
- Legacy route: `r=message/send-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "email=<value>" \
  -d "id_customer=<value>" \
  -d "inbox=<value>" \
  -d "message=<value>" \
  -d "sms=<value>" \
  -d "subject=<value>" \
  -d "wa=<value>" \
  -d "wa_queue=<value>" \
  "https://<domain>/system/api.php/message/send-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "email=<value>" \
  -d "id_customer=<value>" \
  -d "inbox=<value>" \
  -d "message=<value>" \
  -d "sms=<value>" \
  -d "subject=<value>" \
  -d "wa=<value>" \
  -d "wa_queue=<value>" \
  "https://<domain>/system/api.php/message/send-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "email=<value>" \
  -d "id_customer=<value>" \
  -d "inbox=<value>" \
  -d "message=<value>" \
  -d "sms=<value>" \
  -d "subject=<value>" \
  -d "wa=<value>" \
  -d "wa_queue=<value>" \
  "https://<domain>/system/api.php/message/send-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "email=<value>" \
  -d "id_customer=<value>" \
  -d "inbox=<value>" \
  -d "message=<value>" \
  -d "sms=<value>" \
  -d "subject=<value>" \
  -d "wa=<value>" \
  -d "wa_queue=<value>" \
  "https://<domain>/system/api.php/message/send-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "email=<value>" \
  -d "id_customer=<value>" \
  -d "inbox=<value>" \
  -d "message=<value>" \
  -d "sms=<value>" \
  -d "subject=<value>" \
  -d "wa=<value>" \
  -d "wa_queue=<value>" \
  "https://<domain>/system/api.php?r=message/send-post&token=a.<aid>.<time>.<sha1>"
```

### GET /message/send/{id}
- Access: `admin`
- Legacy route: `r=message/send/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/message/send/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/message/send/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/message/send/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/message/send/<id>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=message/send/<id>&token=a.<aid>.<time>.<sha1>"
```

### GET /message/send_bulk
- Access: `admin`
- Legacy route: `r=message/send_bulk`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/message/send_bulk"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/message/send_bulk"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/message/send_bulk"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/message/send_bulk"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=message/send_bulk&token=a.<aid>.<time>.<sha1>"
```

### GET /message/send_bulk_ajax
- Access: `admin`
- Legacy route: `r=message/send_bulk_ajax`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/message/send_bulk_ajax"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/message/send_bulk_ajax"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/message/send_bulk_ajax"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/message/send_bulk_ajax"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=message/send_bulk_ajax&token=a.<aid>.<time>.<sha1>"
```

### GET /message/send_bulk_selected
- Access: `admin`
- Legacy route: `r=message/send_bulk_selected`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/message/send_bulk_selected"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/message/send_bulk_selected"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/message/send_bulk_selected"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/message/send_bulk_selected"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=message/send_bulk_selected&token=a.<aid>.<time>.<sha1>"
```

### POST /message/wa_media_upload
- Access: `admin`
- Legacy route: `r=message/wa_media_upload`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -F "media=@/path/to/file.pdf" \
  "https://<domain>/system/api.php/message/wa_media_upload"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -F "media=@/path/to/file.pdf" \
  "https://<domain>/system/api.php/message/wa_media_upload"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -F "media=@/path/to/file.pdf" \
  "https://<domain>/system/api.php/message/wa_media_upload"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -F "media=@/path/to/file.pdf" \
  "https://<domain>/system/api.php/message/wa_media_upload"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -F "media=@/path/to/file.pdf" \
  "https://<domain>/system/api.php?r=message/wa_media_upload&token=a.<aid>.<time>.<sha1>"
```

## order

### GET /order/balance
- Access: `customer`
- Legacy route: `r=order/balance`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/order/balance"
```

Token (customer, query):
```bash
curl -s "https://<domain>/system/api.php?r=order/balance&token=c.<uid>.<time>.<sha1>"
```

### POST /order/buy/{p2}/{p3}
- Access: `customer`
- Legacy route: `r=order/buy/{p2}/{p3}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  -d "amount=<value>" \
  -d "custom=<value>" \
  -d "discount=<value>" \
  -d "gateway=<value>" \
  "https://<domain>/system/api.php/order/buy/<p2>/<p3>"
```

Token (customer, query):
```bash
curl -s \
  -X POST \
  -d "amount=<value>" \
  -d "custom=<value>" \
  -d "discount=<value>" \
  -d "gateway=<value>" \
  "https://<domain>/system/api.php?r=order/buy/<p2>/<p3>&token=c.<uid>.<time>.<sha1>"
```

### POST /order/gateway/{p3}
- Access: `customer`
- Legacy route: `r=order/gateway/{p3}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  -d "amount=<value>" \
  -d "coupon=<value>" \
  -d "custom=<value>" \
  "https://<domain>/system/api.php/order/gateway/<p3>"
```

Token (customer, query):
```bash
curl -s \
  -X POST \
  -d "amount=<value>" \
  -d "coupon=<value>" \
  -d "custom=<value>" \
  "https://<domain>/system/api.php?r=order/gateway/<p3>&token=c.<uid>.<time>.<sha1>"
```

### GET /order/history
- Access: `customer`
- Legacy route: `r=order/history`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`

Token (customer, X-Token):
```bash
curl -s \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/order/history?p=1"
```

Token (customer, query):
```bash
curl -s "https://<domain>/system/api.php?r=order/history&p=1&token=c.<uid>.<time>.<sha1>"
```

### GET /order/package
- Access: `customer`
- Legacy route: `r=order/package`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/order/package"
```

Token (customer, query):
```bash
curl -s "https://<domain>/system/api.php?r=order/package&token=c.<uid>.<time>.<sha1>"
```

### GET /order/pay
- Access: `customer`
- Legacy route: `r=order/pay`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/order/pay"
```

Token (customer, query):
```bash
curl -s "https://<domain>/system/api.php?r=order/pay&token=c.<uid>.<time>.<sha1>"
```

### POST /order/send/{p2}/{p3}
- Access: `customer`
- Legacy route: `r=order/send/{p2}/{p3}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  -d "username=<value>" \
  "https://<domain>/system/api.php/order/send/<p2>/<p3>"
```

Token (customer, query):
```bash
curl -s \
  -X POST \
  -d "username=<value>" \
  "https://<domain>/system/api.php?r=order/send/<p2>/<p3>&token=c.<uid>.<time>.<sha1>"
```

### GET /order/unpaid
- Access: `customer`
- Legacy route: `r=order/unpaid`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/order/unpaid"
```

Token (customer, query):
```bash
curl -s "https://<domain>/system/api.php?r=order/unpaid&token=c.<uid>.<time>.<sha1>"
```

### GET /order/view/{trxid}/{p3}
- Access: `customer`
- Legacy route: `r=order/view/{trxid}/{p3}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/order/view/<trxid>/<p3>"
```

Token (customer, query):
```bash
curl -s "https://<domain>/system/api.php?r=order/view/<trxid>/<p3>&token=c.<uid>.<time>.<sha1>"
```

### GET /order/voucher
- Access: `customer`
- Legacy route: `r=order/voucher`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/order/voucher"
```

Token (customer, query):
```bash
curl -s "https://<domain>/system/api.php?r=order/voucher&token=c.<uid>.<time>.<sha1>"
```

## page

### GET /page/{page}
- Access: `customer`
- Legacy route: `r=page/{page}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/page/<page>"
```

Token (customer, query):
```bash
curl -s "https://<domain>/system/api.php?r=page/<page>&token=c.<uid>.<time>.<sha1>"
```

## pages

### GET /pages/{action}
- Access: `admin`
- Legacy route: `r=pages/{action}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pages/<action>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pages/<action>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pages/<action>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/pages/<action>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=pages/<action>&token=a.<aid>.<time>.<sha1>"
```

### POST /pages/{action}
- Access: `admin`
- Legacy route: `r=pages/{action}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "html=<value>" \
  -d "template_name=<value>" \
  -d "template_save=<value>" \
  "https://<domain>/system/api.php/pages/<action>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "html=<value>" \
  -d "template_name=<value>" \
  -d "template_save=<value>" \
  "https://<domain>/system/api.php/pages/<action>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "html=<value>" \
  -d "template_name=<value>" \
  -d "template_save=<value>" \
  "https://<domain>/system/api.php/pages/<action>"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "html=<value>" \
  -d "template_name=<value>" \
  -d "template_save=<value>" \
  "https://<domain>/system/api.php/pages/<action>"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "html=<value>" \
  -d "template_name=<value>" \
  -d "template_save=<value>" \
  "https://<domain>/system/api.php?r=pages/<action>&token=a.<aid>.<time>.<sha1>"
```

## paymentgateway

### GET /paymentgateway
- Access: `admin`
- Legacy route: `r=paymentgateway`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/paymentgateway"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/paymentgateway"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/paymentgateway"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/paymentgateway"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=paymentgateway&token=a.<aid>.<time>.<sha1>"
```

### POST /paymentgateway
- Access: `admin`
- Legacy route: `r=paymentgateway`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "pgs[]=<value>" \
  -d "save=<value>" \
  "https://<domain>/system/api.php/paymentgateway"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "pgs[]=<value>" \
  -d "save=<value>" \
  "https://<domain>/system/api.php/paymentgateway"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "pgs[]=<value>" \
  -d "save=<value>" \
  "https://<domain>/system/api.php/paymentgateway"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "pgs[]=<value>" \
  -d "save=<value>" \
  "https://<domain>/system/api.php/paymentgateway"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "pgs[]=<value>" \
  -d "save=<value>" \
  "https://<domain>/system/api.php?r=paymentgateway&token=a.<aid>.<time>.<sha1>"
```

### GET /paymentgateway/audit/{pg}
- Access: `admin`
- Legacy route: `r=paymentgateway/audit/{pg}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`, `q`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/paymentgateway/audit/<pg>?p=1&q=<query>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/paymentgateway/audit/<pg>?p=1&q=<query>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/paymentgateway/audit/<pg>?p=1&q=<query>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/paymentgateway/audit/<pg>?p=1&q=<query>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=paymentgateway/audit/<pg>&p=1&q=<query>&token=a.<aid>.<time>.<sha1>"
```

### GET /paymentgateway/auditview/{id}
- Access: `admin`
- Legacy route: `r=paymentgateway/auditview/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/paymentgateway/auditview/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/paymentgateway/auditview/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/paymentgateway/auditview/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/paymentgateway/auditview/<id>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=paymentgateway/auditview/<id>&token=a.<aid>.<time>.<sha1>"
```

### GET /paymentgateway/delete/{pg}
- Access: `admin`
- Legacy route: `r=paymentgateway/delete/{pg}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/paymentgateway/delete/<pg>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/paymentgateway/delete/<pg>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/paymentgateway/delete/<pg>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/paymentgateway/delete/<pg>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=paymentgateway/delete/<pg>&token=a.<aid>.<time>.<sha1>"
```

## plan

### GET /plan
- Access: `admin`
- Legacy route: `r=plan`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`, `search`, `status`, `router`, `plan`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan?p=1&search=<query>&status=<value>&router=<ROUTER_NAME>&plan=<PLAN_ID>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan?p=1&search=<query>&status=<value>&router=<ROUTER_NAME>&plan=<PLAN_ID>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan?p=1&search=<query>&status=<value>&router=<ROUTER_NAME>&plan=<PLAN_ID>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/plan?p=1&search=<query>&status=<value>&router=<ROUTER_NAME>&plan=<PLAN_ID>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=plan&p=1&search=<query>&status=<value>&router=<ROUTER_NAME>&plan=<PLAN_ID>&token=a.<aid>.<time>.<sha1>"
```

### POST /plan
- Access: `admin`
- Legacy route: `r=plan`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`, `search`, `status`, `router`, `plan`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "plan=<value>" \
  -d "router=<value>" \
  -d "search=<value>" \
  -d "status=<value>" \
  "https://<domain>/system/api.php/plan?p=1&search=<query>&status=<value>&router=<ROUTER_NAME>&plan=<PLAN_ID>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "plan=<value>" \
  -d "router=<value>" \
  -d "search=<value>" \
  -d "status=<value>" \
  "https://<domain>/system/api.php/plan?p=1&search=<query>&status=<value>&router=<ROUTER_NAME>&plan=<PLAN_ID>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "plan=<value>" \
  -d "router=<value>" \
  -d "search=<value>" \
  -d "status=<value>" \
  "https://<domain>/system/api.php/plan?p=1&search=<query>&status=<value>&router=<ROUTER_NAME>&plan=<PLAN_ID>"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "plan=<value>" \
  -d "router=<value>" \
  -d "search=<value>" \
  -d "status=<value>" \
  "https://<domain>/system/api.php/plan?p=1&search=<query>&status=<value>&router=<ROUTER_NAME>&plan=<PLAN_ID>"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "plan=<value>" \
  -d "router=<value>" \
  -d "search=<value>" \
  -d "status=<value>" \
  "https://<domain>/system/api.php?r=plan&p=1&search=<query>&status=<value>&router=<ROUTER_NAME>&plan=<PLAN_ID>&token=a.<aid>.<time>.<sha1>"
```

### GET /plan/add-voucher
- Access: `admin`
- Legacy route: `r=plan/add-voucher`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/add-voucher"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/add-voucher"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/add-voucher"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/plan/add-voucher"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=plan/add-voucher&token=a.<aid>.<time>.<sha1>"
```

### GET /plan/delete/{id}
- Access: `admin`
- Legacy route: `r=plan/delete/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/delete/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/delete/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/delete/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/plan/delete/<id>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=plan/delete/<id>&token=a.<aid>.<time>.<sha1>"
```

### GET /plan/deposit
- Access: `admin`
- Legacy route: `r=plan/deposit`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/deposit"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/deposit"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/deposit"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/plan/deposit"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=plan/deposit&token=a.<aid>.<time>.<sha1>"
```

### POST /plan/deposit-post
- Access: `admin`
- Legacy route: `r=plan/deposit-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `svoucher`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "amount=<value>" \
  -d "id_customer=<value>" \
  -d "id_plan=<value>" \
  -d "note=<value>" \
  "https://<domain>/system/api.php/plan/deposit-post?svoucher=<value>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "amount=<value>" \
  -d "id_customer=<value>" \
  -d "id_plan=<value>" \
  -d "note=<value>" \
  "https://<domain>/system/api.php/plan/deposit-post?svoucher=<value>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "amount=<value>" \
  -d "id_customer=<value>" \
  -d "id_plan=<value>" \
  -d "note=<value>" \
  "https://<domain>/system/api.php/plan/deposit-post?svoucher=<value>"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "amount=<value>" \
  -d "id_customer=<value>" \
  -d "id_plan=<value>" \
  -d "note=<value>" \
  "https://<domain>/system/api.php/plan/deposit-post?svoucher=<value>"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "amount=<value>" \
  -d "id_customer=<value>" \
  -d "id_plan=<value>" \
  -d "note=<value>" \
  "https://<domain>/system/api.php?r=plan/deposit-post&svoucher=<value>&token=a.<aid>.<time>.<sha1>"
```

### POST /plan/edit-post
- Access: `admin`
- Legacy route: `r=plan/edit-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "expiration=<value>" \
  -d "id=<value>" \
  -d "id_plan=<value>" \
  -d "recharged_on=<value>" \
  -d "time=<value>" \
  "https://<domain>/system/api.php/plan/edit-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "expiration=<value>" \
  -d "id=<value>" \
  -d "id_plan=<value>" \
  -d "recharged_on=<value>" \
  -d "time=<value>" \
  "https://<domain>/system/api.php/plan/edit-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "expiration=<value>" \
  -d "id=<value>" \
  -d "id_plan=<value>" \
  -d "recharged_on=<value>" \
  -d "time=<value>" \
  "https://<domain>/system/api.php/plan/edit-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "expiration=<value>" \
  -d "id=<value>" \
  -d "id_plan=<value>" \
  -d "recharged_on=<value>" \
  -d "time=<value>" \
  "https://<domain>/system/api.php/plan/edit-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "expiration=<value>" \
  -d "id=<value>" \
  -d "id_plan=<value>" \
  -d "recharged_on=<value>" \
  -d "time=<value>" \
  "https://<domain>/system/api.php?r=plan/edit-post&token=a.<aid>.<time>.<sha1>"
```

### GET /plan/edit/{id}
- Access: `admin`
- Legacy route: `r=plan/edit/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/edit/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/edit/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/edit/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/plan/edit/<id>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=plan/edit/<id>&token=a.<aid>.<time>.<sha1>"
```

### GET /plan/extend/{id}/{days}
- Access: `admin`
- Legacy route: `r=plan/extend/{id}/{days}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `svoucher`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/extend/<id>/<days>?svoucher=<value>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/extend/<id>/<days>?svoucher=<value>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/extend/<id>/<days>?svoucher=<value>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/plan/extend/<id>/<days>?svoucher=<value>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=plan/extend/<id>/<days>&svoucher=<value>&token=a.<aid>.<time>.<sha1>"
```

### POST /plan/print-voucher
- Access: `admin`
- Legacy route: `r=plan/print-voucher`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "batch=<value>" \
  -d "from_id=<value>" \
  -d "group=<value>" \
  -d "limit=<value>" \
  -d "pagebreak=<value>" \
  -d "planid=<value>" \
  -d "selected_datetime=<value>" \
  -d "vpl=<value>" \
  "https://<domain>/system/api.php/plan/print-voucher"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "batch=<value>" \
  -d "from_id=<value>" \
  -d "group=<value>" \
  -d "limit=<value>" \
  -d "pagebreak=<value>" \
  -d "planid=<value>" \
  -d "selected_datetime=<value>" \
  -d "vpl=<value>" \
  "https://<domain>/system/api.php/plan/print-voucher"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "batch=<value>" \
  -d "from_id=<value>" \
  -d "group=<value>" \
  -d "limit=<value>" \
  -d "pagebreak=<value>" \
  -d "planid=<value>" \
  -d "selected_datetime=<value>" \
  -d "vpl=<value>" \
  "https://<domain>/system/api.php/plan/print-voucher"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "batch=<value>" \
  -d "from_id=<value>" \
  -d "group=<value>" \
  -d "limit=<value>" \
  -d "pagebreak=<value>" \
  -d "planid=<value>" \
  -d "selected_datetime=<value>" \
  -d "vpl=<value>" \
  "https://<domain>/system/api.php/plan/print-voucher"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "batch=<value>" \
  -d "from_id=<value>" \
  -d "group=<value>" \
  -d "limit=<value>" \
  -d "pagebreak=<value>" \
  -d "planid=<value>" \
  -d "selected_datetime=<value>" \
  -d "vpl=<value>" \
  "https://<domain>/system/api.php?r=plan/print-voucher&token=a.<aid>.<time>.<sha1>"
```

### POST /plan/print/{id}
- Access: `admin`
- Legacy route: `r=plan/print/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "id=<value>" \
  "https://<domain>/system/api.php/plan/print/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "id=<value>" \
  "https://<domain>/system/api.php/plan/print/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "id=<value>" \
  "https://<domain>/system/api.php/plan/print/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "id=<value>" \
  "https://<domain>/system/api.php/plan/print/<id>"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "id=<value>" \
  "https://<domain>/system/api.php?r=plan/print/<id>&token=a.<aid>.<time>.<sha1>"
```

### POST /plan/recharge-confirm
- Access: `admin`
- Legacy route: `r=plan/recharge-confirm`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "id_customer=<value>" \
  -d "plan=<value>" \
  -d "server=<value>" \
  -d "using=<value>" \
  -d "note=<optional_note>" \
  "https://<domain>/system/api.php/plan/recharge-confirm"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "id_customer=<value>" \
  -d "plan=<value>" \
  -d "server=<value>" \
  -d "using=<value>" \
  -d "note=<optional_note>" \
  "https://<domain>/system/api.php/plan/recharge-confirm"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "id_customer=<value>" \
  -d "plan=<value>" \
  -d "server=<value>" \
  -d "using=<value>" \
  -d "note=<optional_note>" \
  "https://<domain>/system/api.php/plan/recharge-confirm"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "id_customer=<value>" \
  -d "plan=<value>" \
  -d "server=<value>" \
  -d "using=<value>" \
  -d "note=<optional_note>" \
  "https://<domain>/system/api.php/plan/recharge-confirm"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "id_customer=<value>" \
  -d "plan=<value>" \
  -d "server=<value>" \
  -d "using=<value>" \
  -d "note=<optional_note>" \
  "https://<domain>/system/api.php?r=plan/recharge-confirm&token=a.<aid>.<time>.<sha1>"
```

### POST /plan/recharge-post
- Access: `admin`
- Legacy route: `r=plan/recharge-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "id_customer=<value>" \
  -d "plan=<value>" \
  -d "server=<value>" \
  -d "svoucher=<value>" \
  -d "using=<value>" \
  -d "note=<optional_note>" \
  "https://<domain>/system/api.php/plan/recharge-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "id_customer=<value>" \
  -d "plan=<value>" \
  -d "server=<value>" \
  -d "svoucher=<value>" \
  -d "using=<value>" \
  -d "note=<optional_note>" \
  "https://<domain>/system/api.php/plan/recharge-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "id_customer=<value>" \
  -d "plan=<value>" \
  -d "server=<value>" \
  -d "svoucher=<value>" \
  -d "using=<value>" \
  -d "note=<optional_note>" \
  "https://<domain>/system/api.php/plan/recharge-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "id_customer=<value>" \
  -d "plan=<value>" \
  -d "server=<value>" \
  -d "svoucher=<value>" \
  -d "using=<value>" \
  -d "note=<optional_note>" \
  "https://<domain>/system/api.php/plan/recharge-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "id_customer=<value>" \
  -d "plan=<value>" \
  -d "server=<value>" \
  -d "svoucher=<value>" \
  -d "using=<value>" \
  -d "note=<optional_note>" \
  "https://<domain>/system/api.php?r=plan/recharge-post&token=a.<aid>.<time>.<sha1>"
```

### GET /plan/recharge/{p2}
- Access: `admin`
- Legacy route: `r=plan/recharge/{p2}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/recharge/<p2>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/recharge/<p2>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/recharge/<p2>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/plan/recharge/<p2>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=plan/recharge/<p2>&token=a.<aid>.<time>.<sha1>"
```

### GET /plan/refill
- Access: `admin`
- Legacy route: `r=plan/refill`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/refill"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/refill"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/refill"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/plan/refill"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=plan/refill&token=a.<aid>.<time>.<sha1>"
```

### POST /plan/refill-post
- Access: `admin`
- Legacy route: `r=plan/refill-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "code=<value>" \
  -d "id_customer=<value>" \
  "https://<domain>/system/api.php/plan/refill-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "code=<value>" \
  -d "id_customer=<value>" \
  "https://<domain>/system/api.php/plan/refill-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "code=<value>" \
  -d "id_customer=<value>" \
  "https://<domain>/system/api.php/plan/refill-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "code=<value>" \
  -d "id_customer=<value>" \
  "https://<domain>/system/api.php/plan/refill-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "code=<value>" \
  -d "id_customer=<value>" \
  "https://<domain>/system/api.php?r=plan/refill-post&token=a.<aid>.<time>.<sha1>"
```

### GET /plan/remove-voucher
- Access: `admin`
- Legacy route: `r=plan/remove-voucher`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/remove-voucher"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/remove-voucher"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/remove-voucher"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/plan/remove-voucher"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=plan/remove-voucher&token=a.<aid>.<time>.<sha1>"
```

### GET /plan/sync
- Access: `admin`
- Legacy route: `r=plan/sync`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/sync"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/sync"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/sync"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/plan/sync"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=plan/sync&token=a.<aid>.<time>.<sha1>"
```

### GET /plan/view/{id}/{p3}
- Access: `admin`
- Legacy route: `r=plan/view/{id}/{p3}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/view/<id>/<p3>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/view/<id>/<p3>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/view/<id>/<p3>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/plan/view/<id>/<p3>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=plan/view/<id>/<p3>&token=a.<aid>.<time>.<sha1>"
```

### GET /plan/voucher
- Access: `admin`
- Legacy route: `r=plan/voucher`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `batch_name`, `customer`, `plan`, `router`, `search`, `status`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/voucher?batch_name=<value>&customer=<CUSTOMER_ID>&plan=<PLAN_ID>&router=<ROUTER_NAME>&search=<query>&status=<value>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/voucher?batch_name=<value>&customer=<CUSTOMER_ID>&plan=<PLAN_ID>&router=<ROUTER_NAME>&search=<query>&status=<value>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/voucher?batch_name=<value>&customer=<CUSTOMER_ID>&plan=<PLAN_ID>&router=<ROUTER_NAME>&search=<query>&status=<value>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/plan/voucher?batch_name=<value>&customer=<CUSTOMER_ID>&plan=<PLAN_ID>&router=<ROUTER_NAME>&search=<query>&status=<value>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=plan/voucher&batch_name=<value>&customer=<CUSTOMER_ID>&plan=<PLAN_ID>&router=<ROUTER_NAME>&search=<query>&status=<value>&token=a.<aid>.<time>.<sha1>"
```

### GET /plan/voucher-delete-many
- Access: `admin`
- Legacy route: `r=plan/voucher-delete-many`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/voucher-delete-many"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/voucher-delete-many"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/voucher-delete-many"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/plan/voucher-delete-many"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=plan/voucher-delete-many&token=a.<aid>.<time>.<sha1>"
```

### GET /plan/voucher-delete/{id}
- Access: `admin`
- Legacy route: `r=plan/voucher-delete/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/voucher-delete/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/voucher-delete/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/voucher-delete/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/plan/voucher-delete/<id>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=plan/voucher-delete/<id>&token=a.<aid>.<time>.<sha1>"
```

### POST /plan/voucher-post
- Access: `admin`
- Legacy route: `r=plan/voucher-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "batch_name=<value>" \
  -d "lengthcode=<value>" \
  -d "numbervoucher=<value>" \
  -d "plan=<value>" \
  -d "prefix=<value>" \
  -d "print_now=<value>" \
  -d "server=<value>" \
  -d "type=<value>" \
  -d "voucher_format=<value>" \
  -d "voucher_per_page=<value>" \
  "https://<domain>/system/api.php/plan/voucher-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "batch_name=<value>" \
  -d "lengthcode=<value>" \
  -d "numbervoucher=<value>" \
  -d "plan=<value>" \
  -d "prefix=<value>" \
  -d "print_now=<value>" \
  -d "server=<value>" \
  -d "type=<value>" \
  -d "voucher_format=<value>" \
  -d "voucher_per_page=<value>" \
  "https://<domain>/system/api.php/plan/voucher-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "batch_name=<value>" \
  -d "lengthcode=<value>" \
  -d "numbervoucher=<value>" \
  -d "plan=<value>" \
  -d "prefix=<value>" \
  -d "print_now=<value>" \
  -d "server=<value>" \
  -d "type=<value>" \
  -d "voucher_format=<value>" \
  -d "voucher_per_page=<value>" \
  "https://<domain>/system/api.php/plan/voucher-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "batch_name=<value>" \
  -d "lengthcode=<value>" \
  -d "numbervoucher=<value>" \
  -d "plan=<value>" \
  -d "prefix=<value>" \
  -d "print_now=<value>" \
  -d "server=<value>" \
  -d "type=<value>" \
  -d "voucher_format=<value>" \
  -d "voucher_per_page=<value>" \
  "https://<domain>/system/api.php/plan/voucher-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "batch_name=<value>" \
  -d "lengthcode=<value>" \
  -d "numbervoucher=<value>" \
  -d "plan=<value>" \
  -d "prefix=<value>" \
  -d "print_now=<value>" \
  -d "server=<value>" \
  -d "type=<value>" \
  -d "voucher_format=<value>" \
  -d "voucher_per_page=<value>" \
  "https://<domain>/system/api.php?r=plan/voucher-post&token=a.<aid>.<time>.<sha1>"
```

### GET /plan/voucher-view
- Access: `admin`
- Legacy route: `r=plan/voucher-view`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/voucher-view"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/voucher-view"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plan/voucher-view"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/plan/voucher-view"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=plan/voucher-view&token=a.<aid>.<time>.<sha1>"
```

## plugin

### GET /plugin/{function}
- Access: `mixed`
- Legacy route: `r=plugin/{function}`
- Response: non-JSON (binary/csv/pdf/html/text)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plugin/<function>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plugin/<function>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plugin/<function>"
```

Token (X-Token):
```bash
curl -s \
  -H "X-Token: <admin_or_customer_token>" \
  "https://<domain>/system/api.php/plugin/<function>"
```

Token (query):
```bash
curl -s "https://<domain>/system/api.php?r=plugin/<function>&token=<admin_or_customer_token>"
```

### POST /plugin/{function}
- Access: `mixed`
- Legacy route: `r=plugin/{function}`
- Response: non-JSON (binary/csv/pdf/html/text)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plugin/<function>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plugin/<function>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/plugin/<function>"
```

Token (X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: <admin_or_customer_token>" \
  "https://<domain>/system/api.php/plugin/<function>"
```

Token (query):
```bash
curl -s \
  -X POST \
  "https://<domain>/system/api.php?r=plugin/<function>&token=<admin_or_customer_token>"
```

## pluginmanager

### GET /pluginmanager/delete/{tipe}/{plugin}
- Access: `admin`
- Legacy route: `r=pluginmanager/delete/{tipe}/{plugin}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pluginmanager/delete/<tipe>/<plugin>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pluginmanager/delete/<tipe>/<plugin>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pluginmanager/delete/<tipe>/<plugin>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/pluginmanager/delete/<tipe>/<plugin>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=pluginmanager/delete/<tipe>/<plugin>&token=a.<aid>.<time>.<sha1>"
```

### POST /pluginmanager/dlinstall
- Access: `admin`
- Legacy route: `r=pluginmanager/dlinstall`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "gh_url=<value>" \
  "https://<domain>/system/api.php/pluginmanager/dlinstall"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "gh_url=<value>" \
  "https://<domain>/system/api.php/pluginmanager/dlinstall"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "gh_url=<value>" \
  "https://<domain>/system/api.php/pluginmanager/dlinstall"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "gh_url=<value>" \
  "https://<domain>/system/api.php/pluginmanager/dlinstall"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "gh_url=<value>" \
  "https://<domain>/system/api.php?r=pluginmanager/dlinstall&token=a.<aid>.<time>.<sha1>"
```

### GET /pluginmanager/install/{tipe}/{plugin}
- Access: `admin`
- Legacy route: `r=pluginmanager/install/{tipe}/{plugin}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pluginmanager/install/<tipe>/<plugin>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pluginmanager/install/<tipe>/<plugin>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pluginmanager/install/<tipe>/<plugin>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/pluginmanager/install/<tipe>/<plugin>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=pluginmanager/install/<tipe>/<plugin>&token=a.<aid>.<time>.<sha1>"
```

### GET /pluginmanager/refresh
- Access: `admin`
- Legacy route: `r=pluginmanager/refresh`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pluginmanager/refresh"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pluginmanager/refresh"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pluginmanager/refresh"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/pluginmanager/refresh"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=pluginmanager/refresh&token=a.<aid>.<time>.<sha1>"
```

## pool

### GET /pool/add
- Access: `admin`
- Legacy route: `r=pool/add`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Note: ini endpoint form. Create/update dilakukan via `POST /pool/add-post`.

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pool/add"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pool/add"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pool/add"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/pool/add"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=pool/add&token=a.<aid>.<time>.<sha1>"
```

### GET /pool/add-port
- Access: `admin`
- Legacy route: `r=pool/add-port`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pool/add-port"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pool/add-port"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pool/add-port"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/pool/add-port"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=pool/add-port&token=a.<aid>.<time>.<sha1>"
```

### POST /pool/add-port-post
- Access: `admin`
- Legacy route: `r=pool/add-port-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "name=<value>" \
  -d "port_range=<value>" \
  -d "public_ip=<value>" \
  -d "routers=<value>" \
  "https://<domain>/system/api.php/pool/add-port-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "name=<value>" \
  -d "port_range=<value>" \
  -d "public_ip=<value>" \
  -d "routers=<value>" \
  "https://<domain>/system/api.php/pool/add-port-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "name=<value>" \
  -d "port_range=<value>" \
  -d "public_ip=<value>" \
  -d "routers=<value>" \
  "https://<domain>/system/api.php/pool/add-port-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "name=<value>" \
  -d "port_range=<value>" \
  -d "public_ip=<value>" \
  -d "routers=<value>" \
  "https://<domain>/system/api.php/pool/add-port-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "name=<value>" \
  -d "port_range=<value>" \
  -d "public_ip=<value>" \
  -d "routers=<value>" \
  "https://<domain>/system/api.php?r=pool/add-port-post&token=a.<aid>.<time>.<sha1>"
```

### POST /pool/add-post
- Access: `admin`
- Legacy route: `r=pool/add-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "ip_address=<value>" \
  -d "local_ip=<value>" \
  -d "name=<value>" \
  -d "routers=<value>" \
  "https://<domain>/system/api.php/pool/add-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "ip_address=<value>" \
  -d "local_ip=<value>" \
  -d "name=<value>" \
  -d "routers=<value>" \
  "https://<domain>/system/api.php/pool/add-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "ip_address=<value>" \
  -d "local_ip=<value>" \
  -d "name=<value>" \
  -d "routers=<value>" \
  "https://<domain>/system/api.php/pool/add-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "ip_address=<value>" \
  -d "local_ip=<value>" \
  -d "name=<value>" \
  -d "routers=<value>" \
  "https://<domain>/system/api.php/pool/add-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "ip_address=<value>" \
  -d "local_ip=<value>" \
  -d "name=<value>" \
  -d "routers=<value>" \
  "https://<domain>/system/api.php?r=pool/add-post&token=a.<aid>.<time>.<sha1>"
```

### GET /pool/delete-port/{id}
- Access: `admin`
- Legacy route: `r=pool/delete-port/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pool/delete-port/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pool/delete-port/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pool/delete-port/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/pool/delete-port/<id>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=pool/delete-port/<id>&token=a.<aid>.<time>.<sha1>"
```

### GET /pool/delete/{id}
- Access: `admin`
- Legacy route: `r=pool/delete/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pool/delete/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pool/delete/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pool/delete/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/pool/delete/<id>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=pool/delete/<id>&token=a.<aid>.<time>.<sha1>"
```

### POST /pool/edit-port-post
- Access: `admin`
- Legacy route: `r=pool/edit-port-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "id=<value>" \
  -d "name=<value>" \
  -d "public_ip=<value>" \
  -d "range_port=<value>" \
  -d "routers=<value>" \
  "https://<domain>/system/api.php/pool/edit-port-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "id=<value>" \
  -d "name=<value>" \
  -d "public_ip=<value>" \
  -d "range_port=<value>" \
  -d "routers=<value>" \
  "https://<domain>/system/api.php/pool/edit-port-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "id=<value>" \
  -d "name=<value>" \
  -d "public_ip=<value>" \
  -d "range_port=<value>" \
  -d "routers=<value>" \
  "https://<domain>/system/api.php/pool/edit-port-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "id=<value>" \
  -d "name=<value>" \
  -d "public_ip=<value>" \
  -d "range_port=<value>" \
  -d "routers=<value>" \
  "https://<domain>/system/api.php/pool/edit-port-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "id=<value>" \
  -d "name=<value>" \
  -d "public_ip=<value>" \
  -d "range_port=<value>" \
  -d "routers=<value>" \
  "https://<domain>/system/api.php?r=pool/edit-port-post&token=a.<aid>.<time>.<sha1>"
```

### GET /pool/edit-port/{id}
- Access: `admin`
- Legacy route: `r=pool/edit-port/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pool/edit-port/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pool/edit-port/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pool/edit-port/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/pool/edit-port/<id>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=pool/edit-port/<id>&token=a.<aid>.<time>.<sha1>"
```

### POST /pool/edit-post
- Access: `admin`
- Legacy route: `r=pool/edit-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "id=<value>" \
  -d "ip_address=<value>" \
  -d "local_ip=<value>" \
  -d "routers=<value>" \
  "https://<domain>/system/api.php/pool/edit-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "id=<value>" \
  -d "ip_address=<value>" \
  -d "local_ip=<value>" \
  -d "routers=<value>" \
  "https://<domain>/system/api.php/pool/edit-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "id=<value>" \
  -d "ip_address=<value>" \
  -d "local_ip=<value>" \
  -d "routers=<value>" \
  "https://<domain>/system/api.php/pool/edit-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "id=<value>" \
  -d "ip_address=<value>" \
  -d "local_ip=<value>" \
  -d "routers=<value>" \
  "https://<domain>/system/api.php/pool/edit-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "id=<value>" \
  -d "ip_address=<value>" \
  -d "local_ip=<value>" \
  -d "routers=<value>" \
  "https://<domain>/system/api.php?r=pool/edit-post&token=a.<aid>.<time>.<sha1>"
```

### GET /pool/edit/{id}
- Access: `admin`
- Legacy route: `r=pool/edit/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pool/edit/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pool/edit/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pool/edit/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/pool/edit/<id>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=pool/edit/<id>&token=a.<aid>.<time>.<sha1>"
```

### GET /pool/list
- Access: `admin`
- Legacy route: `r=pool/list`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pool/list?p=1"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pool/list?p=1"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pool/list?p=1"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/pool/list?p=1"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=pool/list&p=1&token=a.<aid>.<time>.<sha1>"
```

### POST /pool/list
- Access: `admin`
- Legacy route: `r=pool/list`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/pool/list?p=1"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/pool/list?p=1"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/pool/list?p=1"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/pool/list?p=1"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "name=<value>" \
  "https://<domain>/system/api.php?r=pool/list&p=1&token=a.<aid>.<time>.<sha1>"
```

### GET /pool/port
- Access: `admin`
- Legacy route: `r=pool/port`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pool/port?p=1"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pool/port?p=1"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pool/port?p=1"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/pool/port?p=1"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=pool/port&p=1&token=a.<aid>.<time>.<sha1>"
```

### POST /pool/port
- Access: `admin`
- Legacy route: `r=pool/port`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/pool/port?p=1"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/pool/port?p=1"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/pool/port?p=1"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/pool/port?p=1"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "name=<value>" \
  "https://<domain>/system/api.php?r=pool/port&p=1&token=a.<aid>.<time>.<sha1>"
```

### GET /pool/sync
- Access: `admin`
- Legacy route: `r=pool/sync`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pool/sync"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pool/sync"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/pool/sync"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/pool/sync"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=pool/sync&token=a.<aid>.<time>.<sha1>"
```

## radius

### GET /radius
- Access: `admin`
- Legacy route: `r=radius`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/radius?p=1"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/radius?p=1"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/radius?p=1"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/radius?p=1"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=radius&p=1&token=a.<aid>.<time>.<sha1>"
```

### POST /radius
- Access: `admin`
- Legacy route: `r=radius`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/radius?p=1"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/radius?p=1"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/radius?p=1"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/radius?p=1"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "name=<value>" \
  "https://<domain>/system/api.php?r=radius&p=1&token=a.<aid>.<time>.<sha1>"
```

### GET /radius/nas-add
- Access: `admin`
- Legacy route: `r=radius/nas-add`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/radius/nas-add"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/radius/nas-add"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/radius/nas-add"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/radius/nas-add"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=radius/nas-add&token=a.<aid>.<time>.<sha1>"
```

### POST /radius/nas-add-post
- Access: `admin`
- Legacy route: `r=radius/nas-add-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "community=<value>" \
  -d "description=<value>" \
  -d "nasname=<value>" \
  -d "ports=<value>" \
  -d "routers=<value>" \
  -d "secret=<value>" \
  -d "server=<value>" \
  -d "shortname=<value>" \
  -d "type=<value>" \
  "https://<domain>/system/api.php/radius/nas-add-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "community=<value>" \
  -d "description=<value>" \
  -d "nasname=<value>" \
  -d "ports=<value>" \
  -d "routers=<value>" \
  -d "secret=<value>" \
  -d "server=<value>" \
  -d "shortname=<value>" \
  -d "type=<value>" \
  "https://<domain>/system/api.php/radius/nas-add-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "community=<value>" \
  -d "description=<value>" \
  -d "nasname=<value>" \
  -d "ports=<value>" \
  -d "routers=<value>" \
  -d "secret=<value>" \
  -d "server=<value>" \
  -d "shortname=<value>" \
  -d "type=<value>" \
  "https://<domain>/system/api.php/radius/nas-add-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "community=<value>" \
  -d "description=<value>" \
  -d "nasname=<value>" \
  -d "ports=<value>" \
  -d "routers=<value>" \
  -d "secret=<value>" \
  -d "server=<value>" \
  -d "shortname=<value>" \
  -d "type=<value>" \
  "https://<domain>/system/api.php/radius/nas-add-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "community=<value>" \
  -d "description=<value>" \
  -d "nasname=<value>" \
  -d "ports=<value>" \
  -d "routers=<value>" \
  -d "secret=<value>" \
  -d "server=<value>" \
  -d "shortname=<value>" \
  -d "type=<value>" \
  "https://<domain>/system/api.php?r=radius/nas-add-post&token=a.<aid>.<time>.<sha1>"
```

### POST /radius/nas-delete/{id}
- Access: `admin`
- Legacy route: `r=radius/nas-delete/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/radius/nas-delete/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/radius/nas-delete/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/radius/nas-delete/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/radius/nas-delete/<id>"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  "https://<domain>/system/api.php?r=radius/nas-delete/<id>&token=a.<aid>.<time>.<sha1>"
```

### POST /radius/nas-edit-post/{id}
- Access: `admin`
- Legacy route: `r=radius/nas-edit-post/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "community=<value>" \
  -d "description=<value>" \
  -d "nasname=<value>" \
  -d "ports=<value>" \
  -d "routers=<value>" \
  -d "secret=<value>" \
  -d "server=<value>" \
  -d "shortname=<value>" \
  -d "type=<value>" \
  "https://<domain>/system/api.php/radius/nas-edit-post/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "community=<value>" \
  -d "description=<value>" \
  -d "nasname=<value>" \
  -d "ports=<value>" \
  -d "routers=<value>" \
  -d "secret=<value>" \
  -d "server=<value>" \
  -d "shortname=<value>" \
  -d "type=<value>" \
  "https://<domain>/system/api.php/radius/nas-edit-post/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "community=<value>" \
  -d "description=<value>" \
  -d "nasname=<value>" \
  -d "ports=<value>" \
  -d "routers=<value>" \
  -d "secret=<value>" \
  -d "server=<value>" \
  -d "shortname=<value>" \
  -d "type=<value>" \
  "https://<domain>/system/api.php/radius/nas-edit-post/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "community=<value>" \
  -d "description=<value>" \
  -d "nasname=<value>" \
  -d "ports=<value>" \
  -d "routers=<value>" \
  -d "secret=<value>" \
  -d "server=<value>" \
  -d "shortname=<value>" \
  -d "type=<value>" \
  "https://<domain>/system/api.php/radius/nas-edit-post/<id>"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "community=<value>" \
  -d "description=<value>" \
  -d "nasname=<value>" \
  -d "ports=<value>" \
  -d "routers=<value>" \
  -d "secret=<value>" \
  -d "server=<value>" \
  -d "shortname=<value>" \
  -d "type=<value>" \
  "https://<domain>/system/api.php?r=radius/nas-edit-post/<id>&token=a.<aid>.<time>.<sha1>"
```

### GET /radius/nas-edit/{id}
- Access: `admin`
- Legacy route: `r=radius/nas-edit/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `name`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/radius/nas-edit/<id>?name=<value>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/radius/nas-edit/<id>?name=<value>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/radius/nas-edit/<id>?name=<value>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/radius/nas-edit/<id>?name=<value>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=radius/nas-edit/<id>&name=<value>&token=a.<aid>.<time>.<sha1>"
```

## register

### POST /register/post
- Access: `public`
- Legacy route: `r=register/post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Public:
```bash
curl -s \
  -X POST \
  -d "address=<value>" \
  -d "cpassword=<value>" \
  -d "email=<value>" \
  -d "fullname=<value>" \
  -d "otp_code=<value>" \
  -d "password=<value>" \
  -d "phone_number=<value>" \
  -d "username=<value>" \
  "https://<domain>/system/api.php/register/post"
```

## reports

### GET /reports/activation
- Access: `admin`
- Legacy route: `r=reports/activation`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`, `q`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/reports/activation?p=1&q=<query>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/reports/activation?p=1&q=<query>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/reports/activation?p=1&q=<query>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/reports/activation?p=1&q=<query>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=reports/activation&p=1&q=<query>&token=a.<aid>.<time>.<sha1>"
```

### POST /reports/activation
- Access: `admin`
- Legacy route: `r=reports/activation`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`, `q`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "keep=<value>" \
  -d "q=<value>" \
  "https://<domain>/system/api.php/reports/activation?p=1&q=<query>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "keep=<value>" \
  -d "q=<value>" \
  "https://<domain>/system/api.php/reports/activation?p=1&q=<query>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "keep=<value>" \
  -d "q=<value>" \
  "https://<domain>/system/api.php/reports/activation?p=1&q=<query>"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "keep=<value>" \
  -d "q=<value>" \
  "https://<domain>/system/api.php/reports/activation?p=1&q=<query>"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "keep=<value>" \
  -d "q=<value>" \
  "https://<domain>/system/api.php?r=reports/activation&p=1&q=<query>&token=a.<aid>.<time>.<sha1>"
```

### GET /reports/ajax/{data}
- Access: `admin`
- Legacy route: `r=reports/ajax/{data}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `ed`, `sd`, `te`, `ts`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/reports/ajax/type?ed=2026-01-31&sd=2026-01-01&te=23:59&ts=00:00"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/reports/ajax/type?ed=2026-01-31&sd=2026-01-01&te=23:59&ts=00:00"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/reports/ajax/type?ed=2026-01-31&sd=2026-01-01&te=23:59&ts=00:00"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/reports/ajax/type?ed=2026-01-31&sd=2026-01-01&te=23:59&ts=00:00"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=reports/ajax/type&ed=2026-01-31&sd=2026-01-01&te=23:59&ts=00:00&token=a.<aid>.<time>.<sha1>"
```

### GET /reports/by-date
- Access: `admin`
- Legacy route: `r=reports/by-date`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`, `q`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/reports/by-date?p=1&q=<query>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/reports/by-date?p=1&q=<query>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/reports/by-date?p=1&q=<query>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/reports/by-date?p=1&q=<query>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=reports/by-date&p=1&q=<query>&token=a.<aid>.<time>.<sha1>"
```

### POST /reports/by-date
- Access: `admin`
- Legacy route: `r=reports/by-date`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`, `q`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "keep=<value>" \
  -d "q=<value>" \
  "https://<domain>/system/api.php/reports/by-date?p=1&q=<query>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "keep=<value>" \
  -d "q=<value>" \
  "https://<domain>/system/api.php/reports/by-date?p=1&q=<query>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "keep=<value>" \
  -d "q=<value>" \
  "https://<domain>/system/api.php/reports/by-date?p=1&q=<query>"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "keep=<value>" \
  -d "q=<value>" \
  "https://<domain>/system/api.php/reports/by-date?p=1&q=<query>"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "keep=<value>" \
  -d "q=<value>" \
  "https://<domain>/system/api.php?r=reports/by-date&p=1&q=<query>&token=a.<aid>.<time>.<sha1>"
```

### GET /reports/by-period
- Access: `admin`
- Legacy route: `r=reports/by-period`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/reports/by-period"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/reports/by-period"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/reports/by-period"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/reports/by-period"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=reports/by-period&token=a.<aid>.<time>.<sha1>"
```

### GET /reports/daily-report
- Access: `admin`
- Legacy route: `r=reports/daily-report`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `ed`, `sd`, `te`, `ts`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/reports/daily-report?ed=2026-01-31&sd=2026-01-01&te=23:59&ts=00:00"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/reports/daily-report?ed=2026-01-31&sd=2026-01-01&te=23:59&ts=00:00"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/reports/daily-report?ed=2026-01-31&sd=2026-01-01&te=23:59&ts=00:00"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/reports/daily-report?ed=2026-01-31&sd=2026-01-01&te=23:59&ts=00:00"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=reports/daily-report&ed=2026-01-31&sd=2026-01-01&te=23:59&ts=00:00&token=a.<aid>.<time>.<sha1>"
```

### POST /reports/period-view
- Access: `admin`
- Legacy route: `r=reports/period-view`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "fdate=<value>" \
  -d "stype=<value>" \
  -d "tdate=<value>" \
  "https://<domain>/system/api.php/reports/period-view"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "fdate=<value>" \
  -d "stype=<value>" \
  -d "tdate=<value>" \
  "https://<domain>/system/api.php/reports/period-view"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "fdate=<value>" \
  -d "stype=<value>" \
  -d "tdate=<value>" \
  "https://<domain>/system/api.php/reports/period-view"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "fdate=<value>" \
  -d "stype=<value>" \
  -d "tdate=<value>" \
  "https://<domain>/system/api.php/reports/period-view"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "fdate=<value>" \
  -d "stype=<value>" \
  -d "tdate=<value>" \
  "https://<domain>/system/api.php?r=reports/period-view&token=a.<aid>.<time>.<sha1>"
```

## routers

### GET /routers
- Access: `admin`
- Legacy route: `r=routers`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/routers?p=1"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/routers?p=1"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/routers?p=1"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/routers?p=1"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=routers&p=1&token=a.<aid>.<time>.<sha1>"
```

### POST /routers
- Access: `admin`
- Legacy route: `r=routers`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/routers?p=1"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/routers?p=1"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/routers?p=1"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/routers?p=1"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "name=<value>" \
  "https://<domain>/system/api.php?r=routers&p=1&token=a.<aid>.<time>.<sha1>"
```

### GET /routers/add
- Access: `admin`
- Legacy route: `r=routers/add`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Note: ini endpoint form. Create/update dilakukan via `POST /routers/add-post`.

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/routers/add"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/routers/add"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/routers/add"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/routers/add"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=routers/add&token=a.<aid>.<time>.<sha1>"
```

### POST /routers/add-post
- Access: `admin`
- Legacy route: `r=routers/add-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "description=<value>" \
  -d "enabled=<value>" \
  -d "ip_address=<value>" \
  -d "name=<value>" \
  -d "password=<value>" \
  -d "testIt=<value>" \
  -d "username=<value>" \
  "https://<domain>/system/api.php/routers/add-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "description=<value>" \
  -d "enabled=<value>" \
  -d "ip_address=<value>" \
  -d "name=<value>" \
  -d "password=<value>" \
  -d "testIt=<value>" \
  -d "username=<value>" \
  "https://<domain>/system/api.php/routers/add-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "description=<value>" \
  -d "enabled=<value>" \
  -d "ip_address=<value>" \
  -d "name=<value>" \
  -d "password=<value>" \
  -d "testIt=<value>" \
  -d "username=<value>" \
  "https://<domain>/system/api.php/routers/add-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "description=<value>" \
  -d "enabled=<value>" \
  -d "ip_address=<value>" \
  -d "name=<value>" \
  -d "password=<value>" \
  -d "testIt=<value>" \
  -d "username=<value>" \
  "https://<domain>/system/api.php/routers/add-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "description=<value>" \
  -d "enabled=<value>" \
  -d "ip_address=<value>" \
  -d "name=<value>" \
  -d "password=<value>" \
  -d "testIt=<value>" \
  -d "username=<value>" \
  "https://<domain>/system/api.php?r=routers/add-post&token=a.<aid>.<time>.<sha1>"
```

### GET /routers/delete/{id}
- Access: `admin`
- Legacy route: `r=routers/delete/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/routers/delete/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/routers/delete/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/routers/delete/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/routers/delete/<id>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=routers/delete/<id>&token=a.<aid>.<time>.<sha1>"
```

### POST /routers/edit-post
- Access: `admin`
- Legacy route: `r=routers/edit-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "coordinates=<value>" \
  -d "coverage=<value>" \
  -d "description=<value>" \
  -d "id=<value>" \
  -d "ip_address=<value>" \
  -d "name=<value>" \
  -d "password=<value>" \
  -d "testIt=<value>" \
  -d "username=<value>" \
  "https://<domain>/system/api.php/routers/edit-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "coordinates=<value>" \
  -d "coverage=<value>" \
  -d "description=<value>" \
  -d "id=<value>" \
  -d "ip_address=<value>" \
  -d "name=<value>" \
  -d "password=<value>" \
  -d "testIt=<value>" \
  -d "username=<value>" \
  "https://<domain>/system/api.php/routers/edit-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "coordinates=<value>" \
  -d "coverage=<value>" \
  -d "description=<value>" \
  -d "id=<value>" \
  -d "ip_address=<value>" \
  -d "name=<value>" \
  -d "password=<value>" \
  -d "testIt=<value>" \
  -d "username=<value>" \
  "https://<domain>/system/api.php/routers/edit-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "coordinates=<value>" \
  -d "coverage=<value>" \
  -d "description=<value>" \
  -d "id=<value>" \
  -d "ip_address=<value>" \
  -d "name=<value>" \
  -d "password=<value>" \
  -d "testIt=<value>" \
  -d "username=<value>" \
  "https://<domain>/system/api.php/routers/edit-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "coordinates=<value>" \
  -d "coverage=<value>" \
  -d "description=<value>" \
  -d "id=<value>" \
  -d "ip_address=<value>" \
  -d "name=<value>" \
  -d "password=<value>" \
  -d "testIt=<value>" \
  -d "username=<value>" \
  "https://<domain>/system/api.php?r=routers/edit-post&token=a.<aid>.<time>.<sha1>"
```

### GET /routers/edit/{id}
- Access: `admin`
- Legacy route: `r=routers/edit/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `name`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/routers/edit/<id>?name=<value>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/routers/edit/<id>?name=<value>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/routers/edit/<id>?name=<value>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/routers/edit/<id>?name=<value>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=routers/edit/<id>&name=<value>&token=a.<aid>.<time>.<sha1>"
```

## search_user

### GET /search_user
- Access: `public`
- Legacy route: `r=search_user`
- Response: non-JSON (binary/csv/pdf/html/text)
- Query params: `query`

Public:
```bash
curl -s "https://<domain>/system/api.php/search_user?query=<query>"
```

## services

### GET /services/add
- Access: `admin`
- Legacy route: `r=services/add`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Note: ini endpoint form. Create/update dilakukan via `POST /services/add-post`.

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/add"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/add"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/add"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/services/add"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=services/add&token=a.<aid>.<time>.<sha1>"
```

### POST /services/add-post
- Access: `admin`
- Legacy route: `r=services/add-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "data_limit=<value>" \
  -d "data_unit=<value>" \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "limit_type=<value>" \
  -d "linked_plans=<value>" \
  -d "name=<value>" \
  -d "plan_type=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "radius=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "sharedusers=<value>" \
  -d "time_limit=<value>" \
  -d "time_unit=<value>" \
  -d "typebp=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/add-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "data_limit=<value>" \
  -d "data_unit=<value>" \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "limit_type=<value>" \
  -d "linked_plans=<value>" \
  -d "name=<value>" \
  -d "plan_type=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "radius=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "sharedusers=<value>" \
  -d "time_limit=<value>" \
  -d "time_unit=<value>" \
  -d "typebp=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/add-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "data_limit=<value>" \
  -d "data_unit=<value>" \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "limit_type=<value>" \
  -d "linked_plans=<value>" \
  -d "name=<value>" \
  -d "plan_type=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "radius=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "sharedusers=<value>" \
  -d "time_limit=<value>" \
  -d "time_unit=<value>" \
  -d "typebp=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/add-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "data_limit=<value>" \
  -d "data_unit=<value>" \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "limit_type=<value>" \
  -d "linked_plans=<value>" \
  -d "name=<value>" \
  -d "plan_type=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "radius=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "sharedusers=<value>" \
  -d "time_limit=<value>" \
  -d "time_unit=<value>" \
  -d "typebp=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/add-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "data_limit=<value>" \
  -d "data_unit=<value>" \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "limit_type=<value>" \
  -d "linked_plans=<value>" \
  -d "name=<value>" \
  -d "plan_type=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "radius=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "sharedusers=<value>" \
  -d "time_limit=<value>" \
  -d "time_unit=<value>" \
  -d "typebp=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php?r=services/add-post&token=a.<aid>.<time>.<sha1>"
```

### GET /services/balance
- Access: `admin`
- Legacy route: `r=services/balance`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/balance?p=1"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/balance?p=1"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/balance?p=1"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/services/balance?p=1"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=services/balance&p=1&token=a.<aid>.<time>.<sha1>"
```

### POST /services/balance
- Access: `admin`
- Legacy route: `r=services/balance`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/services/balance?p=1"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/services/balance?p=1"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/services/balance?p=1"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "name=<value>" \
  "https://<domain>/system/api.php/services/balance?p=1"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "name=<value>" \
  "https://<domain>/system/api.php?r=services/balance&p=1&token=a.<aid>.<time>.<sha1>"
```

### GET /services/balance-add
- Access: `admin`
- Legacy route: `r=services/balance-add`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/balance-add"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/balance-add"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/balance-add"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/services/balance-add"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=services/balance-add&token=a.<aid>.<time>.<sha1>"
```

### POST /services/balance-add-post
- Access: `admin`
- Legacy route: `r=services/balance-add-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "enabled=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name=<value>" \
  -d "price=<value>" \
  -d "reminder_enabled=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/balance-add-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "enabled=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name=<value>" \
  -d "price=<value>" \
  -d "reminder_enabled=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/balance-add-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "enabled=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name=<value>" \
  -d "price=<value>" \
  -d "reminder_enabled=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/balance-add-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "enabled=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name=<value>" \
  -d "price=<value>" \
  -d "reminder_enabled=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/balance-add-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "enabled=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name=<value>" \
  -d "price=<value>" \
  -d "reminder_enabled=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php?r=services/balance-add-post&token=a.<aid>.<time>.<sha1>"
```

### GET /services/balance-delete/{id}
- Access: `admin`
- Legacy route: `r=services/balance-delete/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/balance-delete/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/balance-delete/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/balance-delete/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/services/balance-delete/<id>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=services/balance-delete/<id>&token=a.<aid>.<time>.<sha1>"
```

### POST /services/balance-edit-post
- Access: `admin`
- Legacy route: `r=services/balance-edit-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "enabled=<value>" \
  -d "id=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "price_old=<value>" \
  -d "reminder_enabled=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/balance-edit-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "enabled=<value>" \
  -d "id=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "price_old=<value>" \
  -d "reminder_enabled=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/balance-edit-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "enabled=<value>" \
  -d "id=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "price_old=<value>" \
  -d "reminder_enabled=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/balance-edit-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "enabled=<value>" \
  -d "id=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "price_old=<value>" \
  -d "reminder_enabled=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/balance-edit-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "enabled=<value>" \
  -d "id=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "price_old=<value>" \
  -d "reminder_enabled=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php?r=services/balance-edit-post&token=a.<aid>.<time>.<sha1>"
```

### GET /services/balance-edit/{id}
- Access: `admin`
- Legacy route: `r=services/balance-edit/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/balance-edit/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/balance-edit/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/balance-edit/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/services/balance-edit/<id>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=services/balance-edit/<id>&token=a.<aid>.<time>.<sha1>"
```

### GET /services/delete/{id}
- Access: `admin`
- Legacy route: `r=services/delete/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/delete/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/delete/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/delete/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/services/delete/<id>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=services/delete/<id>&token=a.<aid>.<time>.<sha1>"
```

### POST /services/edit-post
- Access: `admin`
- Legacy route: `r=services/edit-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "data_limit=<value>" \
  -d "data_unit=<value>" \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "limit_type=<value>" \
  -d "linked_plans=<value>" \
  -d "name=<value>" \
  -d "on_login=<value>" \
  -d "on_logout=<value>" \
  -d "plan_expired=<value>" \
  -d "plan_type=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "price_old=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "sharedusers=<value>" \
  -d "time_limit=<value>" \
  -d "time_unit=<value>" \
  -d "typebp=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/edit-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "data_limit=<value>" \
  -d "data_unit=<value>" \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "limit_type=<value>" \
  -d "linked_plans=<value>" \
  -d "name=<value>" \
  -d "on_login=<value>" \
  -d "on_logout=<value>" \
  -d "plan_expired=<value>" \
  -d "plan_type=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "price_old=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "sharedusers=<value>" \
  -d "time_limit=<value>" \
  -d "time_unit=<value>" \
  -d "typebp=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/edit-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "data_limit=<value>" \
  -d "data_unit=<value>" \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "limit_type=<value>" \
  -d "linked_plans=<value>" \
  -d "name=<value>" \
  -d "on_login=<value>" \
  -d "on_logout=<value>" \
  -d "plan_expired=<value>" \
  -d "plan_type=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "price_old=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "sharedusers=<value>" \
  -d "time_limit=<value>" \
  -d "time_unit=<value>" \
  -d "typebp=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/edit-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "data_limit=<value>" \
  -d "data_unit=<value>" \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "limit_type=<value>" \
  -d "linked_plans=<value>" \
  -d "name=<value>" \
  -d "on_login=<value>" \
  -d "on_logout=<value>" \
  -d "plan_expired=<value>" \
  -d "plan_type=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "price_old=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "sharedusers=<value>" \
  -d "time_limit=<value>" \
  -d "time_unit=<value>" \
  -d "typebp=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/edit-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "data_limit=<value>" \
  -d "data_unit=<value>" \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "limit_type=<value>" \
  -d "linked_plans=<value>" \
  -d "name=<value>" \
  -d "on_login=<value>" \
  -d "on_logout=<value>" \
  -d "plan_expired=<value>" \
  -d "plan_type=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "price_old=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "sharedusers=<value>" \
  -d "time_limit=<value>" \
  -d "time_unit=<value>" \
  -d "typebp=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php?r=services/edit-post&token=a.<aid>.<time>.<sha1>"
```

### POST /services/edit-pppoe-post
- Access: `admin`
- Legacy route: `r=services/edit-pppoe-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name_plan=<value>" \
  -d "on_login=<value>" \
  -d "on_logout=<value>" \
  -d "plan_expired=<value>" \
  -d "plan_type=<value>" \
  -d "pool_name=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "price_old=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/edit-pppoe-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name_plan=<value>" \
  -d "on_login=<value>" \
  -d "on_logout=<value>" \
  -d "plan_expired=<value>" \
  -d "plan_type=<value>" \
  -d "pool_name=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "price_old=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/edit-pppoe-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name_plan=<value>" \
  -d "on_login=<value>" \
  -d "on_logout=<value>" \
  -d "plan_expired=<value>" \
  -d "plan_type=<value>" \
  -d "pool_name=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "price_old=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/edit-pppoe-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name_plan=<value>" \
  -d "on_login=<value>" \
  -d "on_logout=<value>" \
  -d "plan_expired=<value>" \
  -d "plan_type=<value>" \
  -d "pool_name=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "price_old=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/edit-pppoe-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name_plan=<value>" \
  -d "on_login=<value>" \
  -d "on_logout=<value>" \
  -d "plan_expired=<value>" \
  -d "plan_type=<value>" \
  -d "pool_name=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "price_old=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php?r=services/edit-pppoe-post&token=a.<aid>.<time>.<sha1>"
```

### POST /services/edit-vpn-post
- Access: `admin`
- Legacy route: `r=services/edit-vpn-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name_plan=<value>" \
  -d "on_login=<value>" \
  -d "on_logout=<value>" \
  -d "plan_expired=<value>" \
  -d "plan_type=<value>" \
  -d "pool_name=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "price_old=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/edit-vpn-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name_plan=<value>" \
  -d "on_login=<value>" \
  -d "on_logout=<value>" \
  -d "plan_expired=<value>" \
  -d "plan_type=<value>" \
  -d "pool_name=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "price_old=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/edit-vpn-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name_plan=<value>" \
  -d "on_login=<value>" \
  -d "on_logout=<value>" \
  -d "plan_expired=<value>" \
  -d "plan_type=<value>" \
  -d "pool_name=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "price_old=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/edit-vpn-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name_plan=<value>" \
  -d "on_login=<value>" \
  -d "on_logout=<value>" \
  -d "plan_expired=<value>" \
  -d "plan_type=<value>" \
  -d "pool_name=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "price_old=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/edit-vpn-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name_plan=<value>" \
  -d "on_login=<value>" \
  -d "on_logout=<value>" \
  -d "plan_expired=<value>" \
  -d "plan_type=<value>" \
  -d "pool_name=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "price_old=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php?r=services/edit-vpn-post&token=a.<aid>.<time>.<sha1>"
```

### GET /services/edit/{id}
- Access: `admin`
- Legacy route: `r=services/edit/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/edit/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/edit/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/edit/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/services/edit/<id>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=services/edit/<id>&token=a.<aid>.<time>.<sha1>"
```

### GET /services/hotspot
- Access: `admin`
- Legacy route: `r=services/hotspot`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`, `bandwidth`, `device`, `name`, `router`, `status`, `type1`, `type2`, `type3`, `valid`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/hotspot?p=1&bandwidth=<value>&device=<value>&name=<value>&router=<ROUTER_NAME>&status=<value>&type1=<value>&type2=<value>&type3=<value>&valid=<value>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/hotspot?p=1&bandwidth=<value>&device=<value>&name=<value>&router=<ROUTER_NAME>&status=<value>&type1=<value>&type2=<value>&type3=<value>&valid=<value>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/hotspot?p=1&bandwidth=<value>&device=<value>&name=<value>&router=<ROUTER_NAME>&status=<value>&type1=<value>&type2=<value>&type3=<value>&valid=<value>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/services/hotspot?p=1&bandwidth=<value>&device=<value>&name=<value>&router=<ROUTER_NAME>&status=<value>&type1=<value>&type2=<value>&type3=<value>&valid=<value>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=services/hotspot&p=1&bandwidth=<value>&device=<value>&name=<value>&router=<ROUTER_NAME>&status=<value>&type1=<value>&type2=<value>&type3=<value>&valid=<value>&token=a.<aid>.<time>.<sha1>"
```

### GET /services/pppoe
- Access: `admin`
- Legacy route: `r=services/pppoe`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`, `bandwidth`, `device`, `name`, `router`, `status`, `type1`, `type2`, `type3`, `valid`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/pppoe?p=1&bandwidth=<value>&device=<value>&name=<value>&router=<ROUTER_NAME>&status=<value>&type1=<value>&type2=<value>&type3=<value>&valid=<value>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/pppoe?p=1&bandwidth=<value>&device=<value>&name=<value>&router=<ROUTER_NAME>&status=<value>&type1=<value>&type2=<value>&type3=<value>&valid=<value>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/pppoe?p=1&bandwidth=<value>&device=<value>&name=<value>&router=<ROUTER_NAME>&status=<value>&type1=<value>&type2=<value>&type3=<value>&valid=<value>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/services/pppoe?p=1&bandwidth=<value>&device=<value>&name=<value>&router=<ROUTER_NAME>&status=<value>&type1=<value>&type2=<value>&type3=<value>&valid=<value>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=services/pppoe&p=1&bandwidth=<value>&device=<value>&name=<value>&router=<ROUTER_NAME>&status=<value>&type1=<value>&type2=<value>&type3=<value>&valid=<value>&token=a.<aid>.<time>.<sha1>"
```

### GET /services/pppoe-add
- Access: `admin`
- Legacy route: `r=services/pppoe-add`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/pppoe-add"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/pppoe-add"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/pppoe-add"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/services/pppoe-add"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=services/pppoe-add&token=a.<aid>.<time>.<sha1>"
```

### POST /services/pppoe-add-post
- Access: `admin`
- Legacy route: `r=services/pppoe-add-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name_plan=<value>" \
  -d "plan_type=<value>" \
  -d "pool_name=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "radius=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/pppoe-add-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name_plan=<value>" \
  -d "plan_type=<value>" \
  -d "pool_name=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "radius=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/pppoe-add-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name_plan=<value>" \
  -d "plan_type=<value>" \
  -d "pool_name=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "radius=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/pppoe-add-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name_plan=<value>" \
  -d "plan_type=<value>" \
  -d "pool_name=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "radius=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/pppoe-add-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name_plan=<value>" \
  -d "plan_type=<value>" \
  -d "pool_name=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "radius=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php?r=services/pppoe-add-post&token=a.<aid>.<time>.<sha1>"
```

### GET /services/pppoe-delete/{id}
- Access: `admin`
- Legacy route: `r=services/pppoe-delete/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/pppoe-delete/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/pppoe-delete/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/pppoe-delete/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/services/pppoe-delete/<id>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=services/pppoe-delete/<id>&token=a.<aid>.<time>.<sha1>"
```

### GET /services/pppoe-edit/{id}
- Access: `admin`
- Legacy route: `r=services/pppoe-edit/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/pppoe-edit/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/pppoe-edit/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/pppoe-edit/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/services/pppoe-edit/<id>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=services/pppoe-edit/<id>&token=a.<aid>.<time>.<sha1>"
```

### GET /services/sync/{target}
- Access: `admin`
- Legacy route: `r=services/sync/{target}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/sync/hotspot"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/sync/hotspot"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/sync/hotspot"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/services/sync/hotspot"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=services/sync/hotspot&token=a.<aid>.<time>.<sha1>"
```

### GET /services/vpn
- Access: `admin`
- Legacy route: `r=services/vpn`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`, `bandwidth`, `device`, `name`, `router`, `status`, `type1`, `type2`, `type3`, `valid`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/vpn?p=1&bandwidth=<value>&device=<value>&name=<value>&router=<ROUTER_NAME>&status=<value>&type1=<value>&type2=<value>&type3=<value>&valid=<value>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/vpn?p=1&bandwidth=<value>&device=<value>&name=<value>&router=<ROUTER_NAME>&status=<value>&type1=<value>&type2=<value>&type3=<value>&valid=<value>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/vpn?p=1&bandwidth=<value>&device=<value>&name=<value>&router=<ROUTER_NAME>&status=<value>&type1=<value>&type2=<value>&type3=<value>&valid=<value>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/services/vpn?p=1&bandwidth=<value>&device=<value>&name=<value>&router=<ROUTER_NAME>&status=<value>&type1=<value>&type2=<value>&type3=<value>&valid=<value>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=services/vpn&p=1&bandwidth=<value>&device=<value>&name=<value>&router=<ROUTER_NAME>&status=<value>&type1=<value>&type2=<value>&type3=<value>&valid=<value>&token=a.<aid>.<time>.<sha1>"
```

### GET /services/vpn-add
- Access: `admin`
- Legacy route: `r=services/vpn-add`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/vpn-add"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/vpn-add"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/vpn-add"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/services/vpn-add"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=services/vpn-add&token=a.<aid>.<time>.<sha1>"
```

### POST /services/vpn-add-post
- Access: `admin`
- Legacy route: `r=services/vpn-add-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name_plan=<value>" \
  -d "plan_type=<value>" \
  -d "pool_name=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "radius=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/vpn-add-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name_plan=<value>" \
  -d "plan_type=<value>" \
  -d "pool_name=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "radius=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/vpn-add-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name_plan=<value>" \
  -d "plan_type=<value>" \
  -d "pool_name=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "radius=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/vpn-add-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name_plan=<value>" \
  -d "plan_type=<value>" \
  -d "pool_name=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "radius=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php/services/vpn-add-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "device=<value>" \
  -d "enabled=<value>" \
  -d "expired_date=<value>" \
  -d "id_bw=<value>" \
  -d "invoice_notification=<value>" \
  -d "linked_plans=<value>" \
  -d "name_plan=<value>" \
  -d "plan_type=<value>" \
  -d "pool_name=<value>" \
  -d "prepaid=<value>" \
  -d "price=<value>" \
  -d "radius=<value>" \
  -d "reminder_enabled=<value>" \
  -d "routers=<value>" \
  -d "validity=<value>" \
  -d "validity_unit=<value>" \
  -d "visibility=<value>" \
  -d "visible_customers=<value>" \
  "https://<domain>/system/api.php?r=services/vpn-add-post&token=a.<aid>.<time>.<sha1>"
```

### GET /services/vpn-delete/{id}
- Access: `admin`
- Legacy route: `r=services/vpn-delete/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/vpn-delete/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/vpn-delete/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/vpn-delete/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/services/vpn-delete/<id>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=services/vpn-delete/<id>&token=a.<aid>.<time>.<sha1>"
```

### GET /services/vpn-edit/{id}
- Access: `admin`
- Legacy route: `r=services/vpn-edit/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/vpn-edit/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/vpn-edit/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/services/vpn-edit/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/services/vpn-edit/<id>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=services/vpn-edit/<id>&token=a.<aid>.<time>.<sha1>"
```

## settings

### GET /settings/api-unblock
- Access: `admin`
- Legacy route: `r=settings/api-unblock`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `csrf_token`, `ip`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/api-unblock?csrf_token=<value>&ip=<value>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/api-unblock?csrf_token=<value>&ip=<value>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/api-unblock?csrf_token=<value>&ip=<value>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/settings/api-unblock?csrf_token=<value>&ip=<value>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=settings/api-unblock&csrf_token=<value>&ip=<value>&token=a.<aid>.<time>.<sha1>"
```

### GET /settings/app
- Access: `admin`
- Legacy route: `r=settings/app`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `testEmail`, `testSms`, `testTg`, `testWa`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/app?testEmail=<value>&testSms=<value>&testTg=<value>&testWa=<value>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/app?testEmail=<value>&testSms=<value>&testTg=<value>&testWa=<value>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/app?testEmail=<value>&testSms=<value>&testTg=<value>&testWa=<value>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/settings/app?testEmail=<value>&testSms=<value>&testTg=<value>&testWa=<value>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=settings/app&testEmail=<value>&testSms=<value>&testTg=<value>&testWa=<value>&token=a.<aid>.<time>.<sha1>"
```

### POST /settings/app-post
- Access: `admin`
- Legacy route: `r=settings/app-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "CompanyName=<value>" \
  -d "custom_tax_rate=<value>" \
  -d "hide_al=<value>" \
  -d "hide_aui=<value>" \
  -d "hide_mrc=<value>" \
  -d "hide_pg=<value>" \
  -d "hide_tms=<value>" \
  -d "hide_uet=<value>" \
  -d "hide_vs=<value>" \
  -d "login_Page_template=<value>" \
  -d "login_page_description=<value>" \
  -d "login_page_head=<value>" \
  -d "login_page_type=<value>" \
  -d "turnstile_admin_enabled=<value>" \
  -d "turnstile_client_enabled=<value>" \
  -d "turnstile_secret_key=<value>" \
  -d "turnstile_site_key=<value>" \
  "https://<domain>/system/api.php/settings/app-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "CompanyName=<value>" \
  -d "custom_tax_rate=<value>" \
  -d "hide_al=<value>" \
  -d "hide_aui=<value>" \
  -d "hide_mrc=<value>" \
  -d "hide_pg=<value>" \
  -d "hide_tms=<value>" \
  -d "hide_uet=<value>" \
  -d "hide_vs=<value>" \
  -d "login_Page_template=<value>" \
  -d "login_page_description=<value>" \
  -d "login_page_head=<value>" \
  -d "login_page_type=<value>" \
  -d "turnstile_admin_enabled=<value>" \
  -d "turnstile_client_enabled=<value>" \
  -d "turnstile_secret_key=<value>" \
  -d "turnstile_site_key=<value>" \
  "https://<domain>/system/api.php/settings/app-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "CompanyName=<value>" \
  -d "custom_tax_rate=<value>" \
  -d "hide_al=<value>" \
  -d "hide_aui=<value>" \
  -d "hide_mrc=<value>" \
  -d "hide_pg=<value>" \
  -d "hide_tms=<value>" \
  -d "hide_uet=<value>" \
  -d "hide_vs=<value>" \
  -d "login_Page_template=<value>" \
  -d "login_page_description=<value>" \
  -d "login_page_head=<value>" \
  -d "login_page_type=<value>" \
  -d "turnstile_admin_enabled=<value>" \
  -d "turnstile_client_enabled=<value>" \
  -d "turnstile_secret_key=<value>" \
  -d "turnstile_site_key=<value>" \
  "https://<domain>/system/api.php/settings/app-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "CompanyName=<value>" \
  -d "custom_tax_rate=<value>" \
  -d "hide_al=<value>" \
  -d "hide_aui=<value>" \
  -d "hide_mrc=<value>" \
  -d "hide_pg=<value>" \
  -d "hide_tms=<value>" \
  -d "hide_uet=<value>" \
  -d "hide_vs=<value>" \
  -d "login_Page_template=<value>" \
  -d "login_page_description=<value>" \
  -d "login_page_head=<value>" \
  -d "login_page_type=<value>" \
  -d "turnstile_admin_enabled=<value>" \
  -d "turnstile_client_enabled=<value>" \
  -d "turnstile_secret_key=<value>" \
  -d "turnstile_site_key=<value>" \
  "https://<domain>/system/api.php/settings/app-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "CompanyName=<value>" \
  -d "custom_tax_rate=<value>" \
  -d "hide_al=<value>" \
  -d "hide_aui=<value>" \
  -d "hide_mrc=<value>" \
  -d "hide_pg=<value>" \
  -d "hide_tms=<value>" \
  -d "hide_uet=<value>" \
  -d "hide_vs=<value>" \
  -d "login_Page_template=<value>" \
  -d "login_page_description=<value>" \
  -d "login_page_head=<value>" \
  -d "login_page_type=<value>" \
  -d "turnstile_admin_enabled=<value>" \
  -d "turnstile_client_enabled=<value>" \
  -d "turnstile_secret_key=<value>" \
  -d "turnstile_site_key=<value>" \
  "https://<domain>/system/api.php?r=settings/app-post&token=a.<aid>.<time>.<sha1>"
```

### GET /settings/change-password
- Access: `admin`
- Legacy route: `r=settings/change-password`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/change-password"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/change-password"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/change-password"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/settings/change-password"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=settings/change-password&token=a.<aid>.<time>.<sha1>"
```

### POST /settings/change-password-post
- Access: `admin`
- Legacy route: `r=settings/change-password-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "cnpass=<value>" \
  -d "npass=<value>" \
  -d "password=<value>" \
  "https://<domain>/system/api.php/settings/change-password-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "cnpass=<value>" \
  -d "npass=<value>" \
  -d "password=<value>" \
  "https://<domain>/system/api.php/settings/change-password-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "cnpass=<value>" \
  -d "npass=<value>" \
  -d "password=<value>" \
  "https://<domain>/system/api.php/settings/change-password-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "cnpass=<value>" \
  -d "npass=<value>" \
  -d "password=<value>" \
  "https://<domain>/system/api.php/settings/change-password-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "cnpass=<value>" \
  -d "npass=<value>" \
  -d "password=<value>" \
  "https://<domain>/system/api.php?r=settings/change-password-post&token=a.<aid>.<time>.<sha1>"
```

### POST /settings/dbbackup
- Access: `admin`
- Legacy route: `r=settings/dbbackup`
- Response: non-JSON (binary/csv/pdf/html/text)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "tables[]=<value>" \
  "https://<domain>/system/api.php/settings/dbbackup"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "tables[]=<value>" \
  "https://<domain>/system/api.php/settings/dbbackup"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "tables[]=<value>" \
  "https://<domain>/system/api.php/settings/dbbackup"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "tables[]=<value>" \
  "https://<domain>/system/api.php/settings/dbbackup"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "tables[]=<value>" \
  "https://<domain>/system/api.php?r=settings/dbbackup&token=a.<aid>.<time>.<sha1>"
```

### POST /settings/dbrestore
- Access: `admin`
- Legacy route: `r=settings/dbrestore`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -F "json=@/path/to/phpnuxbill_backup.json" \
  "https://<domain>/system/api.php/settings/dbrestore"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -F "json=@/path/to/phpnuxbill_backup.json" \
  "https://<domain>/system/api.php/settings/dbrestore"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -F "json=@/path/to/phpnuxbill_backup.json" \
  "https://<domain>/system/api.php/settings/dbrestore"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -F "json=@/path/to/phpnuxbill_backup.json" \
  "https://<domain>/system/api.php/settings/dbrestore"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -F "json=@/path/to/phpnuxbill_backup.json" \
  "https://<domain>/system/api.php?r=settings/dbrestore&token=a.<aid>.<time>.<sha1>"
```

### GET /settings/dbstatus
- Access: `admin`
- Legacy route: `r=settings/dbstatus`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/dbstatus"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/dbstatus"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/dbstatus"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/settings/dbstatus"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=settings/dbstatus&token=a.<aid>.<time>.<sha1>"
```

### GET /settings/devices
- Access: `admin`
- Legacy route: `r=settings/devices`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/devices"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/devices"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/devices"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/settings/devices"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=settings/devices&token=a.<aid>.<time>.<sha1>"
```

### GET /settings/docs
- Access: `admin`
- Legacy route: `r=settings/docs`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/docs"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/docs"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/docs"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/settings/docs"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=settings/docs&token=a.<aid>.<time>.<sha1>"
```

### POST /settings/lang-post
- Access: `admin`
- Legacy route: `r=settings/lang-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/lang-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/lang-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/lang-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/settings/lang-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  "https://<domain>/system/api.php?r=settings/lang-post&token=a.<aid>.<time>.<sha1>"
```

### GET /settings/language
- Access: `admin`
- Legacy route: `r=settings/language`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/language"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/language"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/language"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/settings/language"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=settings/language&token=a.<aid>.<time>.<sha1>"
```

### GET /settings/localisation
- Access: `admin`
- Legacy route: `r=settings/localisation`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/localisation"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/localisation"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/localisation"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/settings/localisation"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=settings/localisation&token=a.<aid>.<time>.<sha1>"
```

### POST /settings/localisation-post
- Access: `admin`
- Legacy route: `r=settings/localisation-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "country_code_phone=<value>" \
  -d "date_format=<value>" \
  -d "hotspot_plan=<value>" \
  -d "lan=<value>" \
  -d "pppoe_plan=<value>" \
  -d "radius_plan=<value>" \
  -d "tzone=<value>" \
  -d "vpn_plan=<value>" \
  "https://<domain>/system/api.php/settings/localisation-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "country_code_phone=<value>" \
  -d "date_format=<value>" \
  -d "hotspot_plan=<value>" \
  -d "lan=<value>" \
  -d "pppoe_plan=<value>" \
  -d "radius_plan=<value>" \
  -d "tzone=<value>" \
  -d "vpn_plan=<value>" \
  "https://<domain>/system/api.php/settings/localisation-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "country_code_phone=<value>" \
  -d "date_format=<value>" \
  -d "hotspot_plan=<value>" \
  -d "lan=<value>" \
  -d "pppoe_plan=<value>" \
  -d "radius_plan=<value>" \
  -d "tzone=<value>" \
  -d "vpn_plan=<value>" \
  "https://<domain>/system/api.php/settings/localisation-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "country_code_phone=<value>" \
  -d "date_format=<value>" \
  -d "hotspot_plan=<value>" \
  -d "lan=<value>" \
  -d "pppoe_plan=<value>" \
  -d "radius_plan=<value>" \
  -d "tzone=<value>" \
  -d "vpn_plan=<value>" \
  "https://<domain>/system/api.php/settings/localisation-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "country_code_phone=<value>" \
  -d "date_format=<value>" \
  -d "hotspot_plan=<value>" \
  -d "lan=<value>" \
  -d "pppoe_plan=<value>" \
  -d "radius_plan=<value>" \
  -d "tzone=<value>" \
  -d "vpn_plan=<value>" \
  "https://<domain>/system/api.php?r=settings/localisation-post&token=a.<aid>.<time>.<sha1>"
```

### POST /settings/maintenance
- Access: `admin`
- Legacy route: `r=settings/maintenance`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "save=<value>" \
  "https://<domain>/system/api.php/settings/maintenance"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "save=<value>" \
  "https://<domain>/system/api.php/settings/maintenance"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "save=<value>" \
  "https://<domain>/system/api.php/settings/maintenance"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "save=<value>" \
  "https://<domain>/system/api.php/settings/maintenance"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "save=<value>" \
  "https://<domain>/system/api.php?r=settings/maintenance&token=a.<aid>.<time>.<sha1>"
```

### POST /settings/miscellaneous
- Access: `admin`
- Legacy route: `r=settings/miscellaneous`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "save=<value>" \
  "https://<domain>/system/api.php/settings/miscellaneous"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "save=<value>" \
  "https://<domain>/system/api.php/settings/miscellaneous"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "save=<value>" \
  "https://<domain>/system/api.php/settings/miscellaneous"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "save=<value>" \
  "https://<domain>/system/api.php/settings/miscellaneous"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "save=<value>" \
  "https://<domain>/system/api.php?r=settings/miscellaneous&token=a.<aid>.<time>.<sha1>"
```

### GET /settings/notifications
- Access: `admin`
- Legacy route: `r=settings/notifications`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/notifications"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/notifications"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/notifications"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/settings/notifications"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=settings/notifications&token=a.<aid>.<time>.<sha1>"
```

### POST /settings/notifications-post
- Access: `admin`
- Legacy route: `r=settings/notifications-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/notifications-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/notifications-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/notifications-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/settings/notifications-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  "https://<domain>/system/api.php?r=settings/notifications-post&token=a.<aid>.<time>.<sha1>"
```

### POST /settings/notifications-test
- Access: `admin`
- Legacy route: `r=settings/notifications-test`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "message=<value>" \
  -d "phone=<value>" \
  -d "template=<value>" \
  "https://<domain>/system/api.php/settings/notifications-test"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "message=<value>" \
  -d "phone=<value>" \
  -d "template=<value>" \
  "https://<domain>/system/api.php/settings/notifications-test"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "message=<value>" \
  -d "phone=<value>" \
  -d "template=<value>" \
  "https://<domain>/system/api.php/settings/notifications-test"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "message=<value>" \
  -d "phone=<value>" \
  -d "template=<value>" \
  "https://<domain>/system/api.php/settings/notifications-test"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "message=<value>" \
  -d "phone=<value>" \
  -d "template=<value>" \
  "https://<domain>/system/api.php?r=settings/notifications-test&token=a.<aid>.<time>.<sha1>"
```

### GET /settings/users
- Access: `admin`
- Legacy route: `r=settings/users`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`, `search`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/users?p=1&search=<query>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/users?p=1&search=<query>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/users?p=1&search=<query>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/settings/users?p=1&search=<query>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=settings/users&p=1&search=<query>&token=a.<aid>.<time>.<sha1>"
```

### GET /settings/users-add
- Access: `admin`
- Legacy route: `r=settings/users-add`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/users-add"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/users-add"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/users-add"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/settings/users-add"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=settings/users-add&token=a.<aid>.<time>.<sha1>"
```

### GET /settings/users-delete/{id}
- Access: `admin`
- Legacy route: `r=settings/users-delete/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/users-delete/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/users-delete/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/users-delete/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/settings/users-delete/<id>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=settings/users-delete/<id>&token=a.<aid>.<time>.<sha1>"
```

### POST /settings/users-edit-post
- Access: `admin`
- Legacy route: `r=settings/users-edit-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "city=<value>" \
  -d "cpassword=<value>" \
  -d "email=<value>" \
  -d "faceDetect=<value>" \
  -d "fullname=<value>" \
  -d "id=<value>" \
  -d "password=<value>" \
  -d "phone=<value>" \
  -d "root=<value>" \
  -d "status=<value>" \
  -d "subdistrict=<value>" \
  -d "user_type=<value>" \
  -d "username=<value>" \
  -d "ward=<value>" \
  "https://<domain>/system/api.php/settings/users-edit-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "city=<value>" \
  -d "cpassword=<value>" \
  -d "email=<value>" \
  -d "faceDetect=<value>" \
  -d "fullname=<value>" \
  -d "id=<value>" \
  -d "password=<value>" \
  -d "phone=<value>" \
  -d "root=<value>" \
  -d "status=<value>" \
  -d "subdistrict=<value>" \
  -d "user_type=<value>" \
  -d "username=<value>" \
  -d "ward=<value>" \
  "https://<domain>/system/api.php/settings/users-edit-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "city=<value>" \
  -d "cpassword=<value>" \
  -d "email=<value>" \
  -d "faceDetect=<value>" \
  -d "fullname=<value>" \
  -d "id=<value>" \
  -d "password=<value>" \
  -d "phone=<value>" \
  -d "root=<value>" \
  -d "status=<value>" \
  -d "subdistrict=<value>" \
  -d "user_type=<value>" \
  -d "username=<value>" \
  -d "ward=<value>" \
  "https://<domain>/system/api.php/settings/users-edit-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "city=<value>" \
  -d "cpassword=<value>" \
  -d "email=<value>" \
  -d "faceDetect=<value>" \
  -d "fullname=<value>" \
  -d "id=<value>" \
  -d "password=<value>" \
  -d "phone=<value>" \
  -d "root=<value>" \
  -d "status=<value>" \
  -d "subdistrict=<value>" \
  -d "user_type=<value>" \
  -d "username=<value>" \
  -d "ward=<value>" \
  "https://<domain>/system/api.php/settings/users-edit-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "city=<value>" \
  -d "cpassword=<value>" \
  -d "email=<value>" \
  -d "faceDetect=<value>" \
  -d "fullname=<value>" \
  -d "id=<value>" \
  -d "password=<value>" \
  -d "phone=<value>" \
  -d "root=<value>" \
  -d "status=<value>" \
  -d "subdistrict=<value>" \
  -d "user_type=<value>" \
  -d "username=<value>" \
  -d "ward=<value>" \
  "https://<domain>/system/api.php?r=settings/users-edit-post&token=a.<aid>.<time>.<sha1>"
```

### GET /settings/users-edit/{id}/{p3}
- Access: `admin`
- Legacy route: `r=settings/users-edit/{id}/{p3}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/users-edit/<id>/<p3>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/users-edit/<id>/<p3>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/users-edit/<id>/<p3>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/settings/users-edit/<id>/<p3>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=settings/users-edit/<id>/<p3>&token=a.<aid>.<time>.<sha1>"
```

### POST /settings/users-post
- Access: `admin`
- Legacy route: `r=settings/users-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "city=<value>" \
  -d "email=<value>" \
  -d "fullname=<value>" \
  -d "password=<value>" \
  -d "phone=<value>" \
  -d "root=<value>" \
  -d "send_notif=<value>" \
  -d "subdistrict=<value>" \
  -d "user_type=<value>" \
  -d "username=<value>" \
  -d "ward=<value>" \
  "https://<domain>/system/api.php/settings/users-post"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "city=<value>" \
  -d "email=<value>" \
  -d "fullname=<value>" \
  -d "password=<value>" \
  -d "phone=<value>" \
  -d "root=<value>" \
  -d "send_notif=<value>" \
  -d "subdistrict=<value>" \
  -d "user_type=<value>" \
  -d "username=<value>" \
  -d "ward=<value>" \
  "https://<domain>/system/api.php/settings/users-post"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "city=<value>" \
  -d "email=<value>" \
  -d "fullname=<value>" \
  -d "password=<value>" \
  -d "phone=<value>" \
  -d "root=<value>" \
  -d "send_notif=<value>" \
  -d "subdistrict=<value>" \
  -d "user_type=<value>" \
  -d "username=<value>" \
  -d "ward=<value>" \
  "https://<domain>/system/api.php/settings/users-post"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "city=<value>" \
  -d "email=<value>" \
  -d "fullname=<value>" \
  -d "password=<value>" \
  -d "phone=<value>" \
  -d "root=<value>" \
  -d "send_notif=<value>" \
  -d "subdistrict=<value>" \
  -d "user_type=<value>" \
  -d "username=<value>" \
  -d "ward=<value>" \
  "https://<domain>/system/api.php/settings/users-post"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "city=<value>" \
  -d "email=<value>" \
  -d "fullname=<value>" \
  -d "password=<value>" \
  -d "phone=<value>" \
  -d "root=<value>" \
  -d "send_notif=<value>" \
  -d "subdistrict=<value>" \
  -d "user_type=<value>" \
  -d "username=<value>" \
  -d "ward=<value>" \
  "https://<domain>/system/api.php?r=settings/users-post&token=a.<aid>.<time>.<sha1>"
```

### GET /settings/users-view/{id}
- Access: `admin`
- Legacy route: `r=settings/users-view/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/users-view/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/users-view/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/settings/users-view/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/settings/users-view/<id>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=settings/users-view/<id>&token=a.<aid>.<time>.<sha1>"
```

## voucher

### GET /voucher/activation
- Access: `customer`
- Legacy route: `r=voucher/activation`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `code`

Token (customer, X-Token):
```bash
curl -s \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/voucher/activation?code=<value>"
```

Token (customer, query):
```bash
curl -s "https://<domain>/system/api.php?r=voucher/activation&code=<value>&token=c.<uid>.<time>.<sha1>"
```

### POST /voucher/activation-post
- Access: `customer`
- Legacy route: `r=voucher/activation-post`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  -d "code=<value>" \
  "https://<domain>/system/api.php/voucher/activation-post"
```

Token (customer, query):
```bash
curl -s \
  -X POST \
  -d "code=<value>" \
  "https://<domain>/system/api.php?r=voucher/activation-post&token=c.<uid>.<time>.<sha1>"
```

### GET /voucher/invoice
- Access: `customer`
- Legacy route: `r=voucher/invoice`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/voucher/invoice"
```

Token (customer, query):
```bash
curl -s "https://<domain>/system/api.php?r=voucher/invoice&token=c.<uid>.<time>.<sha1>"
```

### GET /voucher/invoice/{id}
- Access: `customer`
- Legacy route: `r=voucher/invoice/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Token (customer, X-Token):
```bash
curl -s \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/voucher/invoice/<id>"
```

Token (customer, query):
```bash
curl -s "https://<domain>/system/api.php?r=voucher/invoice/<id>&token=c.<uid>.<time>.<sha1>"
```

### GET /voucher/list-activated
- Access: `customer`
- Legacy route: `r=voucher/list-activated`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `p`

Token (customer, X-Token):
```bash
curl -s \
  -H "X-Token: c.<uid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/voucher/list-activated?p=1"
```

Token (customer, query):
```bash
curl -s "https://<domain>/system/api.php?r=voucher/list-activated&p=1&token=c.<uid>.<time>.<sha1>"
```

## widgets

### GET /widgets
- Access: `admin`
- Legacy route: `r=widgets`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)
- Query params: `user`

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/widgets?user=<USERNAME>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/widgets?user=<USERNAME>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/widgets?user=<USERNAME>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/widgets?user=<USERNAME>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=widgets&user=<USERNAME>&token=a.<aid>.<time>.<sha1>"
```

### GET /widgets/add/{position}
- Access: `admin`
- Legacy route: `r=widgets/add/{position}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/widgets/add/<position>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/widgets/add/<position>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/widgets/add/<position>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/widgets/add/<position>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=widgets/add/<position>&token=a.<aid>.<time>.<sha1>"
```

### POST /widgets/add/{position}
- Access: `admin`
- Legacy route: `r=widgets/add/{position}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "content=<value>" \
  -d "enabled=<value>" \
  -d "orders=<value>" \
  -d "position=<value>" \
  -d "tipeUser=<value>" \
  -d "title=<value>" \
  -d "widget=<value>" \
  "https://<domain>/system/api.php/widgets/add/<position>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "content=<value>" \
  -d "enabled=<value>" \
  -d "orders=<value>" \
  -d "position=<value>" \
  -d "tipeUser=<value>" \
  -d "title=<value>" \
  -d "widget=<value>" \
  "https://<domain>/system/api.php/widgets/add/<position>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "content=<value>" \
  -d "enabled=<value>" \
  -d "orders=<value>" \
  -d "position=<value>" \
  -d "tipeUser=<value>" \
  -d "title=<value>" \
  -d "widget=<value>" \
  "https://<domain>/system/api.php/widgets/add/<position>"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "content=<value>" \
  -d "enabled=<value>" \
  -d "orders=<value>" \
  -d "position=<value>" \
  -d "tipeUser=<value>" \
  -d "title=<value>" \
  -d "widget=<value>" \
  "https://<domain>/system/api.php/widgets/add/<position>"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "content=<value>" \
  -d "enabled=<value>" \
  -d "orders=<value>" \
  -d "position=<value>" \
  -d "tipeUser=<value>" \
  -d "title=<value>" \
  -d "widget=<value>" \
  "https://<domain>/system/api.php?r=widgets/add/<position>&token=a.<aid>.<time>.<sha1>"
```

### GET /widgets/delete/{id}
- Access: `admin`
- Legacy route: `r=widgets/delete/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/widgets/delete/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/widgets/delete/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/widgets/delete/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/widgets/delete/<id>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=widgets/delete/<id>&token=a.<aid>.<time>.<sha1>"
```

### GET /widgets/edit/{id}
- Access: `admin`
- Legacy route: `r=widgets/edit/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/widgets/edit/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/widgets/edit/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php/widgets/edit/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  "https://<domain>/system/api.php/widgets/edit/<id>"
```

Token (admin, query):
```bash
curl -s "https://<domain>/system/api.php?r=widgets/edit/<id>&token=a.<aid>.<time>.<sha1>"
```

### POST /widgets/edit/{id}
- Access: `admin`
- Legacy route: `r=widgets/edit/{id}`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "content=<value>" \
  -d "enabled=<value>" \
  -d "id=<value>" \
  -d "orders=<value>" \
  -d "position=<value>" \
  -d "tipeUser=<value>" \
  -d "title=<value>" \
  -d "widget=<value>" \
  "https://<domain>/system/api.php/widgets/edit/<id>"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "content=<value>" \
  -d "enabled=<value>" \
  -d "id=<value>" \
  -d "orders=<value>" \
  -d "position=<value>" \
  -d "tipeUser=<value>" \
  -d "title=<value>" \
  -d "widget=<value>" \
  "https://<domain>/system/api.php/widgets/edit/<id>"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "content=<value>" \
  -d "enabled=<value>" \
  -d "id=<value>" \
  -d "orders=<value>" \
  -d "position=<value>" \
  -d "tipeUser=<value>" \
  -d "title=<value>" \
  -d "widget=<value>" \
  "https://<domain>/system/api.php/widgets/edit/<id>"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "content=<value>" \
  -d "enabled=<value>" \
  -d "id=<value>" \
  -d "orders=<value>" \
  -d "position=<value>" \
  -d "tipeUser=<value>" \
  -d "title=<value>" \
  -d "widget=<value>" \
  "https://<domain>/system/api.php/widgets/edit/<id>"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "content=<value>" \
  -d "enabled=<value>" \
  -d "id=<value>" \
  -d "orders=<value>" \
  -d "position=<value>" \
  -d "tipeUser=<value>" \
  -d "title=<value>" \
  -d "widget=<value>" \
  "https://<domain>/system/api.php?r=widgets/edit/<id>&token=a.<aid>.<time>.<sha1>"
```

### POST /widgets/pos
- Access: `admin`
- Legacy route: `r=widgets/pos`
- Response: JSON envelope (`success`, `message`, `result`, `meta`)

Admin API key (X-Admin-Api-Key, recommended):
```bash
curl -s \
  -X POST \
  -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  -d "id[]=<value>" \
  -d "orders[]=<value>" \
  "https://<domain>/system/api.php/widgets/pos"
```

Admin API key (X-API-Key):
```bash
curl -s \
  -X POST \
  -H "X-API-Key: <ADMIN_API_KEY>" \
  -d "id[]=<value>" \
  -d "orders[]=<value>" \
  "https://<domain>/system/api.php/widgets/pos"
```

Admin API key (Authorization: Bearer):
```bash
curl -s \
  -X POST \
  -H "Authorization: Bearer <ADMIN_API_KEY>" \
  -d "id[]=<value>" \
  -d "orders[]=<value>" \
  "https://<domain>/system/api.php/widgets/pos"
```

Token (admin, X-Token):
```bash
curl -s \
  -X POST \
  -H "X-Token: a.<aid>.<time>.<sha1>" \
  -d "id[]=<value>" \
  -d "orders[]=<value>" \
  "https://<domain>/system/api.php/widgets/pos"
```

Token (admin, query):
```bash
curl -s \
  -X POST \
  -d "id[]=<value>" \
  -d "orders[]=<value>" \
  "https://<domain>/system/api.php?r=widgets/pos&token=a.<aid>.<time>.<sha1>"
```
