import zipfile
import os
import re
import shutil
import xml.etree.ElementTree as ET

docx_path = r'storage\app\templates\pv_ouverture.docx'
temp_dir = r'storage\app\templates\temp_pv2'

if os.path.exists(temp_dir):
    shutil.rmtree(temp_dir)

with zipfile.ZipFile(docx_path, 'r') as zip_ref:
    zip_ref.extractall(temp_dir)

doc_xml_path = os.path.join(temp_dir, 'word', 'document.xml')
with open(doc_xml_path, 'r', encoding='utf-8') as f:
    xml = f.read()

# Global replacements
xml = xml.replace('04/2024/DRCA-RSK', '${numero_ao}')
xml = xml.replace('25/11/2024', '${date_ouverture}')
xml = xml.replace('10:00', '${heure_ouverture}')
xml = xml.replace('06/2024/DRCA-RSK', '${num_decision_nomination}')
xml = xml.replace('Prestations topographiques de délimitation des terrains des CDA, relevant de l’ORMVAG, objet du transfert du patrimoine à l’ONCA', '${objet_ao}')
xml = xml.replace('#NOM?', '${attributaire_lettres}')
xml = xml.replace('10:45', '${heure_levee}')

# The tables
# Find all <w:tr>
trs = re.findall(r'<w:tr\b.*?</w:tr>', xml)

new_xml = xml
for tr in trs:
    text = re.sub(r'<[^>]+>', '', tr)
    if 'AKABBABI ABDELLATIF' in text:
        # Commission row 1
        new_tr = tr.replace('AKABBABI ABDELLATIF', '${commission_nom}')
        new_tr = new_tr.replace('Technicien de 2 éme grade/ Responsable des affaires juridiques et patrimoine foncier', '${commission_fonction}')
        new_tr = new_tr.replace('>Président<', '>${commission_qualite}<')
        new_xml = new_xml.replace(tr, new_tr)
    elif 'TAOUIL Hasnaa' in text or 'OULD ABBOU IBTISSAM' in text:
        # Delete the other static commission rows, we only need the template row!
        # Actually, if we delete them, it's cleaner. But the cloneRow will just clone the first one.
        # So we delete the extra rows!
        new_xml = new_xml.replace(tr, '')
    elif 'BUREAU ALAOUI TOPO' in text and 'KHENIFRA' in text:
        # Participant row
        new_tr = tr.replace('1', '${participant_n}') # Be careful! The number 1 might appear elsewhere in the tr xml (e.g. w:w="1"). Let's use re.sub with text nodes.
        # A safer way to replace '1' when it's the text content:
        new_tr = re.sub(r'(<w:t[^>]*>)1(</w:t>)', r'\1${participant_n}\2', new_tr)
        new_tr = new_tr.replace('BUREAU ALAOUI TOPO', '${participant_nom}')
        new_tr = new_tr.replace('APP N 6 Immeuble 01 Place Hommane El Fatouaki, KHENIFRA, Maroc', '${participant_adresse}')
        new_tr = new_tr.replace('KHENIFRA', '${participant_ville}')
        new_xml = new_xml.replace(tr, new_tr)
    elif '2' in text and '--' in text: # Extra participant rows
        new_xml = new_xml.replace(tr, '')
    elif '3' in text and '--' in text:
        new_xml = new_xml.replace(tr, '')
    elif 'BUREAU ALAOUI TOPO' in text and '39 060,00' in text:
        # Offers row
        new_tr = re.sub(r'(<w:t[^>]*>)1(</w:t>)', r'\1${offre_n}\2', tr)
        new_tr = new_tr.replace('BUREAU ALAOUI TOPO', '${offre_nom}')
        # There are two 39 060,00. Let's just replace the first one with offre_montant and second with offre_montant_apres
        # Since it's string replace, replace all 39 060,00 with ${offre_montant} is easier and sufficient for our needs.
        # But wait, we specified ${offre_montant} and ${offre_montant_apres}.
        # Let's do it cleanly:
        pieces = new_tr.split('39 060,00')
        if len(pieces) == 3:
            new_tr = pieces[0] + '${offre_montant}' + pieces[1] + '${offre_montant_apres}' + pieces[2]
        new_xml = new_xml.replace(tr, new_tr)
        
# For the attributaire text outside the table:
# "présentée ici par la société : BUREAU ALAOUI TOPO Pour un montant global (TTC) de -"
# We need to replace "BUREAU ALAOUI TOPO" with "${attributaire_nom}" only in the paragraph.
# Wait, replacing 'BUREAU ALAOUI TOPO' globally would affect the tables too.
# But we already did the table rows and stored them in `new_tr`. If we replace globally NOW, it will only affect the remaining text!
new_xml = new_xml.replace('BUREAU ALAOUI TOPO', '${attributaire_nom}')
new_xml = new_xml.replace('>-<', '>${attributaire_montant}<') # The text '-' is used for the amount in the template "de -"

# Strip any XML tags inside ${...}
def clean_var(match):
    return re.sub(r'<[^>]+>', '', match.group(0))

new_xml = re.sub(r'\$\{.*?\}', clean_var, new_xml)

with open(doc_xml_path, 'w', encoding='utf-8') as f:
    f.write(new_xml)

new_docx_path = r'storage\app\templates\pv_ouverture.docx'
with zipfile.ZipFile(new_docx_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
    for root, dirs, files in os.walk(temp_dir):
        for file in files:
            file_path = os.path.join(root, file)
            arcname = os.path.relpath(file_path, temp_dir)
            zipf.write(file_path, arcname)

print("PV cleaned")
