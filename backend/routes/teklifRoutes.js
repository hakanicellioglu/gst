const express = require('express');
const router = express.Router();
const teklifController = require('../controllers/teklifController');
const auth = require('../middlewares/authMiddleware');

router.get('/test', (req, res) => res.json({ message: 'Teklif route çalışıyor.' }));

router.post('/', auth, teklifController.createTeklif);
router.get('/', auth, teklifController.getAllTeklif);
router.get('/:id', auth, teklifController.getTeklif);
router.put('/:id', auth, teklifController.updateTeklif);
router.delete('/:id', auth, teklifController.deleteTeklif);
router.get('/:id/pdf', auth, teklifController.generateTeklifPDF);
router.get('/:id/excel', auth, teklifController.generateTeklifExcel);

module.exports = router;