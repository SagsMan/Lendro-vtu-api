# Lendro VTU App


## Stack

| Layer | Technology |
|---|---|
| Backend | PHP 
| Frontend | React (no bundler — `React.createElement` via CDN) |
| Database | MySQL
| Payments | SquadCo (wallet funding) 
| VTU Provider A | CheapDataHub — airtime, data, electricity, cable, education
| VTU Provider B | ConnectBridge — airtime, data, cable, electricity, education



## Architecture

```
lendro/
├── app.html                        # Single-page app shell (loads all JS components)
├── assets/
│   ├── components/
│   │   ├── ehelper.jsx             # Global config: icons, colours, categoryBillers, categoryTitles
│   │   ├── ehome.jsx               # Home page component
│   │   ├── eservices.jsx           # Partner Services page + ServicesOnHome widget
│   │   ├── epopups.jsx             # All purchase forms + Buy() function + wallet auto-refresh
│   │   └── ...
│   └── images/                     # Network logos (mtn.jpg, airtel.jpg, glo.jpg, 9mobile.jpg …)
└── api/v1/
    ├── configs.php                 # DB credentials, markup constants
    ├── db.php                      # PDO singleton + session start + helpers include
    ├── helpers/helpers.php         # getAllServices(), requireAuth(), generateRefNo() …
    ├── providers/
    │   ├── BaseProvider.php        # HTTP request() + auth handling (Bearer / Token)
    │   ├── ProviderA.php           # CheapDataHub integration
    │   ├── ProviderB.php           # ConnectBridge integration
    │   ├── ProviderProductsA.php   # Static CheapDataHub product catalogue
    │   └── ProviderResponseNormalizer.php
    ├── client/
    │   ├── order.php               # POST /api/v1/client/order  — place purchase
    │   ├── wallet.php              # GET  /api/v1/client/wallet — balance + transactions
    │   ├── verify.php              # POST /api/v1/client/verify — smart card / meter lookup
    │   ├── show.php                # GET  /api/v1/client/show   — data plan list for a network
    │   └── services.php            # GET  /api/v1/client/services — all grouped services
    ├── services/index.php          # Alias for client/services (used by frontend)
    ├── TransactionService.php      # Wallet debit + provider dispatch + DB write
    └── workers/                    # Background job that actually calls provider APIs
ting API workaround) |
