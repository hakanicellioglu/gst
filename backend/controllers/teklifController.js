const Teklif = require('../models/teklifModel');
const { generateTeklifPDF } = require('../utils/pdfService');
const { generateTeklifExcel } = require('../utils/excelService');


// Teklif oluştur
exports.createTeklif = async (req, res) => {
  try {
    const newTeklif = new Teklif({
      ...req.body,
      createdBy: req.user.id
    });
    await newTeklif.save();
    res.status(201).json(newTeklif);
  } catch (err) {
    console.error(err);
    res.status(500).json({ message: 'Teklif oluşturulamadı.' });
  }
};

// Tüm teklifleri getir
exports.getAllTeklif = async (req, res) => {
  try {
    const teklifler = await Teklif.find().sort({ createdAt: -1 });
    res.status(200).json(teklifler);
  } catch (err) {
    console.error(err);
    res.status(500).json({ message: 'Teklifler getirilemedi.' });
  }
};

// Teklif detay
exports.getTeklif = async (req, res) => {
  try {
    const teklif = await Teklif.findById(req.params.id);
    if (!teklif) return res.status(404).json({ message: 'Teklif bulunamadı.' });
    res.status(200).json(teklif);
  } catch (err) {
    console.error(err);
    res.status(500).json({ message: 'Teklif getirilemedi.' });
  }
};

// Teklif güncelle
exports.updateTeklif = async (req, res) => {
  try {
    const updated = await Teklif.findByIdAndUpdate(
      req.params.id,
      req.body,
      { new: true }
    );
    res.status(200).json(updated);
  } catch (err) {
    console.error(err);
    res.status(500).json({ message: 'Teklif güncellenemedi.' });
  }
};

// Teklif sil
exports.deleteTeklif = async (req, res) => {
  try {
    await Teklif.findByIdAndDelete(req.params.id);
    res.status(200).json({ message: 'Teklif silindi.' });
  } catch (err) {
    console.error(err);
    res.status(500).json({ message: 'Teklif silinemedi.' });
  }
};

// PDF üret
exports.generateTeklifPDF = async (req, res) => {
  try {
    console.log("PDF endpoint çağrıldı.");
    const teklif = await Teklif.findById(req.params.id);
    if (!teklif) {
      console.log("Teklif bulunamadı.");
      return res.status(404).json({ message: 'Teklif bulunamadı.' });
    }
    console.log("Teklif bulundu, PDF oluşturuluyor...");
    generateTeklifPDF(teklif, res);
    console.log("PDF response gönderildi.");
  } catch (err) {
    console.error("PDF oluşturulurken hata:", err);
    res.status(500).json({ message: 'PDF oluşturulamadı.' });
  }
};

// Excel üret
exports.generateTeklifExcel = async (req, res) => {
try {
    const teklif = await Teklif.findById(req.params.id);
    if (!teklif) return res.status(404).json({ message: 'Teklif bulunamadı.' });

    await generateTeklifExcel(teklif, res);
  } catch (err) {
    console.error(err);
    res.status(500).json({ message: 'Excel oluşturulamadı.' });
  }
};