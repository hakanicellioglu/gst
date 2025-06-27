const mongoose = require('mongoose');

const TeklifSchema = new mongoose.Schema({
  teklifTipi: {
    type: String,
    enum: ['giyotin', 'surme', 'karma'],
    required: true
  },
  baslik: String,
  musteriAdi: String,
  tarih: {
    type: Date,
    default: Date.now
  },
  sistemAdedi: Number,
  sistemTipi: String,
  kanatTipi: String,
  kenetTipi: String,
  ralKodu: String,
  dovizCinsi: String,
  dovizKuru: Number,
  genislik: Number,
  yukseklik: Number,
  kombinasyon: String,
  camRengi: String,
  aluKg: Number,
  camM2: Number,
  delta: String,
  toplamTutar: Number,
  iskontoOrani: Number,
  kdvOrani: Number,
  vergilerDahilToplam: Number,
  urunler: [
    {
      isim: String,
      kod: String,
      olcu: String,
      adet: Number,
      metraj: Number,
      tip: String
    }
  ],
  createdBy: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User'
  }
}, {
  timestamps: true
});

module.exports = mongoose.model('Teklif', TeklifSchema);
