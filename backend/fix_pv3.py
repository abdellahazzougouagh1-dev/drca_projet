import zipfile
import os
import shutil

docx_path = r'storage\app\templates\pv_ouverture.docx'
temp_dir = r'storage\app\templates\temp_pv4'

if os.path.exists(temp_dir):
    shutil.rmtree(temp_dir)

with zipfile.ZipFile(docx_path, 'r') as zip_ref:
    zip_ref.extractall(temp_dir)

doc_xml_path = os.path.join(temp_dir, 'word', 'document.xml')
with open(doc_xml_path, 'r', encoding='utf-8') as f:
    xml = f.read()

# Replace the specific decision date
xml = xml.replace('en date du${date_ouverture}composée', 'en date du${date_preparation}composée')
xml = xml.replace('en date du ${date_ouverture} composée', 'en date du ${date_preparation} composée')

# Wait, the XML might have tags between them.
# Let's just do a string replace of `>${date_ouverture}<` to `>${date_preparation}<` if we can find the context.
# Or better, regex!
import re
# Look for "décision ... en date du ... ${date_ouverture}"
# It's easier: just look for the text around it in the original: 
# "décision N°${num_decision_nomination}en date du${date_ouverture}composée de :"
xml = re.sub(r'en date du(</w:t>)?(<[^>]+>)?\$\{date_ouverture\}', r'en date du\1\2${date_preparation}', xml, count=1)

# Also at the end: "Fait à ${lieu_signature} le: ${date_ouverture}"
# Let's change it to ${date_signature} or just use ${date_ouverture} which is fine.
# We will create ${date_signature}
xml = re.sub(r'le(</w:t>)?(<[^>]+>)?\$\{date_ouverture\}', r'le\1\2${date_signature}', xml, count=1)

with open(doc_xml_path, 'w', encoding='utf-8') as f:
    f.write(xml)

with zipfile.ZipFile(docx_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
    for root, dirs, files in os.walk(temp_dir):
        for file in files:
            file_path = os.path.join(root, file)
            arcname = os.path.relpath(file_path, temp_dir)
            zipf.write(file_path, arcname)

print("PV updated with date_preparation and date_signature")
