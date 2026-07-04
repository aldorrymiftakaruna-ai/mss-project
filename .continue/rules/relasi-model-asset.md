---
description: Gunakan aturan ini saat menambahkan model baru yang berelasi dengan
  Asset, atau saat ada error method undefined di Asset.
---

Setiap kali menambahkan model baru yang memiliki foreign key ke tabel assets (asset_id), HARUS menambahkan relasi hasMany/hasOne ke model Asset. Cek model Asset setelah migration/model baru dibuat untuk memastikan tidak ada error "Call to undefined method ...::riskScores()".