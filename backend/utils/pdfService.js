const PDFDocument = require('pdfkit');

function generateTeklifPDF(teklif, res) {
  const doc = new PDFDocument();
  doc.font('Helvetica');

  // PDF response headers
  res.setHeader('Content-Type', 'application/pdf');
  res.setHeader('Content-Disposition', `attachment; filename=teklif_${teklif._id}.pdf`);

  // PDF çıktısını response'a pipe et
  doc.pipe(res);

  // Başlık
  doc.fontSize(20).text(`Teklif Bilgileri`, { underline: true });
  doc.moveDown();

  // Temel bilgiler
  doc.fontSize(12).text(`Teklif ID: ${teklif._id}`);
  doc.text(`Başlık: ${teklif.baslik || '-'}`);
  doc.text(`Müşteri: ${teklif.musteriAdi || '-'}`);
  doc.text(`Sistem Tipi: ${teklif.sistemTipi || '-'}`);
  doc.text(`Sistem Adedi: ${teklif.sistemAdedi || '-'}`);
  doc.text(`Teklif Tipi: ${teklif.teklifTipi || '-'}`);
  doc.text(`Tarih: ${new Date(teklif.tarih).toLocaleDateString()}`);
  doc.text(`Toplam Tutar: ${teklif.vergilerDahilToplam?.toLocaleString('tr-TR')} ₺`);

  doc.moveDown();

  // Ürünler
  if (teklif.urunler && teklif.urunler.length > 0) {
    doc.fontSize(14).text(`Ürünler:`);
    doc.moveDown(0.5);
    teklif.urunler.forEach((u, i) => {
      doc.fontSize(12).text(`${i + 1}. ${u.isim} - ${u.adet || 0} adet`);
    });
  } else {
    doc.text(`Ürün bilgisi bulunmamaktadır.`);
  }

  // PDF işlemini finalize et
  doc.end();
}

module.exports = { generateTeklifPDF };
