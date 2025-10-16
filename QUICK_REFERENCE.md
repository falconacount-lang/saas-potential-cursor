# 📚 QUICK REFERENCE

## 🎯 Key Files & Locations

### Configuration
- **Master Config**: `/.env.example` (200+ lines, all settings)
- **Copy to**: `.env` (fill with your values)

### Database
- **Working DB**: `/backend/database/adilgfx.sqlite`
- **Master Schema**: `/backend/database/complete_schema.sql`
- **Tables**: 30+
- **Apply**: `sqlite3 backend/database/adilgfx.sqlite < backend/database/complete_schema.sql`

### Admin Panel
- **Location**: `/src/admin/` (React)
- **Access**: Frontend route `/admin`
- **Login**: admin@adilcreator.com / Muhadilmmad#11213
- **Features**: 18+ sections

### Deployment
- **Package**: `/hostinger-deployment/`
- **Upload to**: `public_html/` on Hostinger
- **Guide**: `README_DEPLOYMENT.md`

---

## ⚡ Quick Commands

```bash
# Development
npm install && npm run dev
php -S localhost:8000 -t backend

# Build
npm run build

# Test
php verify-endpoints.php

# Deploy
# Upload hostinger-deployment/* to server
```

---

## 📖 Documentation Map

| File | Purpose | Read Time |
|------|---------|-----------|
| **START_HERE.md** | Start here! | 3 min |
| **README.md** | Project overview | 5 min |
| **README_DEPLOYMENT.md** | Deploy in 5 min | 5 min |
| **.env.example** | All configuration | 10 min |
| **ROCKET_SITE_COMPLETE.md** | All features | 15 min |
| **DEPLOYMENT_GUIDE.md** | Detailed deployment | 10 min |
| **FINAL_DEPLOYMENT_CHECKLIST.md** | Pre-deploy checklist | 5 min |
| **CLEANUP_AND_CONSOLIDATION.md** | What was cleaned | 5 min |
| **ALL_VERIFIED.md** | Verification report | 3 min |
| **rocket_site_plan.md** | Original plan | 20 min |

---

## 🔗 Important URLs

### Development:
- Frontend: http://localhost:5173
- Backend: http://localhost:8000
- Admin: http://localhost:5173/admin
- API Test: http://localhost:8000/api/test

### Production:
- Site: https://adilcreator.com
- Admin: https://adilcreator.com/admin
- API: https://adilcreator.com/backend/api/test
- Backend: https://adilcreator.com/backend

---

## 🎯 Next Steps

1. **New User?** → Read `START_HERE.md`
2. **Deploying?** → Read `README_DEPLOYMENT.md`
3. **Features?** → Read `ROCKET_SITE_COMPLETE.md`
4. **Issues?** → Check relevant doc above

---

**Everything you need is documented! 🚀**
