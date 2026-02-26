const fs = require('fs');
const path = require('path');
const localesDir = path.join(__dirname, 'client/src/i18n/locales');
const files = fs.readdirSync(localesDir).filter(f => f.endsWith('.json'));

const translations = {
    en: { edit_device: 'Edit Device', custom_name_label: 'Custom Name' },
    es: { edit_device: 'Editar Dispositivo', custom_name_label: 'Nombre Personalizado' },
    ca: { edit_device: 'Editar Dispositiu', custom_name_label: 'Nom Personalitzat' },
    de: { edit_device: 'Gerät bearbeiten', custom_name_label: 'Benutzerdefinierter Name' },
    eu: { edit_device: 'Gailua Editatu', custom_name_label: 'Izen Pertsonalizatua' },
    fr: { edit_device: "Modifier l'appareil", custom_name_label: 'Nom Personnalisé' },
    gl: { edit_device: 'Editar Dispositivo', custom_name_label: 'Nome Personalizado' },
    pt: { edit_device: 'Editar Dispositivo', custom_name_label: 'Nome Personalizado' }
};

files.forEach(file => {
    const lang = file.replace('.json', '');
    const filePath = path.join(localesDir, file);
    const data = JSON.parse(fs.readFileSync(filePath, 'utf8'));

    if (data.devices) {
        const t = translations[lang] || translations['en'];
        data.devices.edit_device = t.edit_device;
        data.devices.custom_name_label = t.custom_name_label;
        fs.writeFileSync(filePath, JSON.stringify(data, null, 4) + '\n');
        console.log(`Updated ${lang}.json`);
    }
});
