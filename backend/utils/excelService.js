const ExcelJS = require('exceljs');

async function generateTeklifExcel(teklif, res) {
  const workbook = new ExcelJS.Workbook();
  const sheet = workbook.addWorksheet('Teklif');

  sheet.addRow(['Teklif Başlık', teklif.baslik || '-']);
  sheet.addRow(['Müşteri', teklif.musteriAdi || '-']);
  sheet.addRow(['Tarih', new Date(teklif.tarih).toLocaleDateString()]);
  sheet.addRow(['Toplam Tutar', teklif.vergilerDahilToplam?.toLocaleString('tr-TR') + ' ₺']);
  sheet.addRow([]);
  sheet.addRow(['Ürünler']);
  sheet.addRow(['#', 'İsim', 'Adet']);

  teklif.urunler.forEach((urun, index) => {
    sheet.addRow([index + 1, urun.isim, urun.adet || 0]);
  });

  res.setHeader(
    'Content-Type',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
  );
  res.setHeader(
    'Content-Disposition',
    `attachment; filename=teklif_${teklif._id}.xlsx`
  );

  await workbook.xlsx.write(res);
  res.end();
}

module.exports = { generateTeklifExcel };
