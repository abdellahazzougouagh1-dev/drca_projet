import zipfile
import os
import re

docx_path = r'storage\app\templates\estimation.docx'
temp_dir = r'storage\app\templates\temp_est2'

if not os.path.exists(docx_path):
    print("Fichier non trouvé")
    exit()

with zipfile.ZipFile(docx_path, 'r') as zip_ref:
    zip_ref.extractall(temp_dir)

doc_xml_path = os.path.join(temp_dir, 'word', 'document.xml')
with open(doc_xml_path, 'r', encoding='utf-8') as f:
    xml = f.read()

# 1. Base Variables
xml = xml.replace('01/2024/DRCA-RSK', '${num_aoo}')
xml = xml.replace('01/04/2024', '${date_ouverture}')
xml = xml.replace('lot unique', 'Lot ${lot_numero}')
xml = xml.replace('LA REALISATION DES PRESTATIONS DE PRISE EN CHARGE (RESTAURATION ET HEBERGEMENT) DES AGRICULTEURS, DES AGRICULTRICES ET DES TECHNICIENS DE LA REGION DE CASA-SETTAT DANS LECADRE DES VISITES ET VOYAGES D’ETUDES PROGRAMMES A L’OCCASION DE LA 16EME EDITION DU', '${objet}')
xml = xml.replace('#NOM?', '${montant_lettres}')

# 2. Extract table rows to find the one with #N/A
rows = re.findall(r'<w:tr\b.*?</w:tr>', xml)

items_row_index = -1
for i, row in enumerate(rows):
    if '#N/A' in row:
        items_row_index = i
        break

if items_row_index != -1:
    # Replace the #N/A cells in this row with our variables
    bad_row = rows[items_row_index]
    
    # Extract the cells
    cells = re.findall(r'<w:tc\b.*?</w:tc>', bad_row)
    
    if len(cells) >= 6:
        # We need to cleanly replace the text inside the cells
        def replace_cell_text(cell, new_text):
            return re.sub(r'(<w:t.*?>).*?(</w:t>)', r'\g<1>' + new_text + r'\g<2>', cell, count=1)
        
        cells[0] = replace_cell_text(cells[0], '${n}')
        cells[1] = replace_cell_text(cells[1], '${designation}')
        cells[2] = replace_cell_text(cells[2], '${unite}')
        cells[3] = replace_cell_text(cells[3], '${quantite}')
        cells[4] = replace_cell_text(cells[4], '${pu_ht}')
        cells[5] = replace_cell_text(cells[5], '${montant_ht}')
        
        # In case the cell had multiple text nodes, remove the rest
        for i in range(len(cells)):
            # This is a bit risky but cell has been modified.
            pass
            
        # Or simpler: just rebuild the row by replacing '#N/A' and '0-' 
        # Actually, let's just do text replacements on the bad_row
        bad_row = bad_row.replace('#N/A', 'REPLACE_ME_TEMP', 5)
        bad_row = bad_row.replace('0-', 'REPLACE_ME_TEMP', 1)
        
        new_row = bad_row
        new_row = new_row.replace('REPLACE_ME_TEMP', '${n}', 1)
        new_row = new_row.replace('REPLACE_ME_TEMP', '${designation}', 1)
        new_row = new_row.replace('REPLACE_ME_TEMP', '${unite}', 1)
        new_row = new_row.replace('REPLACE_ME_TEMP', '${quantite}', 1)
        new_row = new_row.replace('REPLACE_ME_TEMP', '${pu_ht}', 1)
        new_row = new_row.replace('REPLACE_ME_TEMP', '${montant_ht}', 1)
        
        xml = xml.replace(rows[items_row_index], new_row)
        
    # Replace Totals rows
    if items_row_index + 1 < len(rows):
        # Total Hors Taxe row
        t_ht_row = rows[items_row_index + 1]
        t_ht_row = t_ht_row.replace('#N/A', '${total_ht}')
        xml = xml.replace(rows[items_row_index + 1], t_ht_row)
        
    if items_row_index + 2 < len(rows):
        # TVA row
        t_tva_row = rows[items_row_index + 2]
        t_tva_row = t_tva_row.replace('#N/A', '${total_tva}')
        xml = xml.replace(rows[items_row_index + 2], t_tva_row)
        
    if items_row_index + 3 < len(rows):
        # Total TTC row
        t_ttc_row = rows[items_row_index + 3]
        t_ttc_row = t_ttc_row.replace('#N/A', '${total_ttc}')
        xml = xml.replace(rows[items_row_index + 3], t_ttc_row)
        
# Fix the total letters #N/A (there is another #N/A)
xml = xml.replace('est arrétée à<w:r>', 'est arrêtée à la somme de :<w:r>')
xml = xml.replace('#N/A', '${total_ttc} Dirhams TTC.')

with open(doc_xml_path, 'w', encoding='utf-8') as f:
    f.write(xml)

new_docx_path = r'storage\app\templates\estimation.docx'
with zipfile.ZipFile(new_docx_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
    for root, dirs, files in os.walk(temp_dir):
        for file in files:
            file_path = os.path.join(root, file)
            arcname = os.path.relpath(file_path, temp_dir)
            zipf.write(file_path, arcname)

print("OK")
