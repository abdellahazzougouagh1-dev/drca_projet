import zipfile
import os

docx_path = r'storage\app\templates\avis_publication_fr.docx'
temp_dir = r'storage\app\templates\temp_py_docx'

if not os.path.exists(docx_path):
    print("Fichier non trouvé")
    exit()

# Extract
with zipfile.ZipFile(docx_path, 'r') as zip_ref:
    zip_ref.extractall(temp_dir)

doc_xml_path = os.path.join(temp_dir, 'word', 'document.xml')
with open(doc_xml_path, 'r', encoding='utf-8') as f:
    xml = f.read()

# Character replacements
replacements = {
    'Ã©': 'é',
    'Ã ': 'à',
    'Ã¢': 'â',
    'Ãª': 'ê',
    'Ã¨': 'è',
    'â€™': '’',
    'Â°': '°',
    'Ã®': 'î',
    'Ã´': 'ô',
    'Ã§': 'ç',
    'Ã»': 'û',
    'Ã\xa0': 'à',  # C3 A0 is Ã + NBSP
}

for bad, good in replacements.items():
    xml = xml.replace(bad, good)
    
# Manual variable fix
xml = xml.replace('<w:t>Lot </w:t><w:r><w:rPr><w:b/><w:spacing w:val="-3"/><w:lang w:val="en-US"/></w:rPr><w:t xml:space="preserve">${lot_numero} : ${cautionnement_ttc_lettres} (${cautionnement_ttc})', '<w:t>Lot </w:t><w:r><w:rPr><w:b/><w:spacing w:val="-3"/><w:lang w:val="en-US"/></w:rPr><w:t xml:space="preserve">${lot_numero} : ${estimation_ttc_lettres} (${estimation_ttc})')
# Or simpler:
# Find the first occurrence of cautionnement and replace it.
xml = xml.replace('cautionnement_ttc_lettres', 'estimation_ttc_lettres', 1)
xml = xml.replace('cautionnement_ttc', 'estimation_ttc', 1)

# Ensure "à" encoding fix didn't miss anything
xml = xml.replace('Ã ', 'à ')

with open(doc_xml_path, 'w', encoding='utf-8') as f:
    f.write(xml)

# Rezip
new_docx_path = r'storage\app\templates\avis_publication_fr_fixed.docx'
with zipfile.ZipFile(new_docx_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
    for root, dirs, files in os.walk(temp_dir):
        for file in files:
            file_path = os.path.join(root, file)
            arcname = os.path.relpath(file_path, temp_dir)
            zipf.write(file_path, arcname)

print("OK")
