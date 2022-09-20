const { createGettextFiles } = require('./createGettextFiles');
const fetch = require('node-fetch')

/**
 * Downloads translations from Google Sheets and run createGettextFiles with the received data.
 *
 * @see https://docs.google.com/spreadsheets/d/1WSx25YNJRyOZpkuJZLLY6hrNufe25SJaGH4dgX_og4I/edit#gid=0
 */
function downloadTranslations() {
  const documentId = '1WSx25YNJRyOZpkuJZLLY6hrNufe25SJaGH4dgX_og4I';
  const sheetId = '1574429380';
  const url = `https://docs.google.com/spreadsheets/d/${documentId}/export?format=csv&id=${documentId}&gid=${sheetId}`;

  fetch(url)
    .then(data => data.buffer())
    .then(createGettextFiles)
}

module.exports = { downloadTranslations };
