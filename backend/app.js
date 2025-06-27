require('dotenv').config();
const express = require('express');
const cors = require('cors');
const morgan = require('morgan');
const bodyParser = require('body-parser');


const connectDB = require('./config/db');

const app = express();

// Middleware
app.use(cors({
  origin: '*',
  methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
  allowedHeaders: ['Content-Type', 'Authorization'],
}));
app.use(bodyParser.json());
app.use(morgan('dev'));

// Routes
const authRoutes = require('./routes/authRoutes');
const teklifRoutes = require('./routes/teklifRoutes');

app.use('/api/auth', authRoutes);
app.use('/api/teklif', teklifRoutes);

// Veritabanına bağlan
connectDB();

const PORT = process.env.PORT || 5000;
app.listen(PORT, () => console.log(`Server ${PORT} portunda çalışıyor.`));
