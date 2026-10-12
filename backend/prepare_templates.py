import os
from docx import Document

def prepare_template(path, out_path):
    doc = Document(path)
    
    # 1. First, let's find the global variables and replace them
    for p in doc.paragraphs:
        if '01/2024/DRCA-RSK' in p.text:
            p.text = p.text.replace('01/2024/DRCA-RSK', '${numero_ao}')
        if '01/04/2024' in p.text:
            p.text = p.text.replace('01/04/2024', '${date_preparation}')
        if 'LA REALISATION DES PRESTATIONS DE PRISE EN CHARGE' in p.text:
            p.text = '${objet_ao}'
        if '03/08/2023' in p.text:
            p.text = p.text.replace('03/08/2023', '${date_signature}')

    # 2. Re-create the tables to be 1-column so cloneRow works
    # Tables are:
    # 0: depot
    # 1: evince admin/tech
    # 2: admis sans reserve
    # 3: admis avec reserve
    # 4: evince additif
    # 5: offres
    # 6: gagnant

    # Since AooDocumentGenerationService currently just dumps everything into ONE list of concurrents,
    # let's just create ONE unified table in the document and remove the others to make it work,
    # OR we can just replace the tables with the tags `AooDocumentGenerationService` expects:
    # ${concurrent_n}, ${concurrent_nom}, ${concurrent_montant}, ${concurrent_statut}, ${concurrent_motif}

    # Clear all tables first
    for i, table in enumerate(doc.tables):
        for row in table.rows:
            for cell in row.cells:
                cell.text = ''
    
    if len(doc.tables) > 5:
        # We will make Table 5 the "unified results table"
        table = doc.tables[5]
        # Make sure it has enough cells
        if len(table.rows) > 0 and len(table.rows[0].cells) > 0:
            table.rows[0].cells[0].text = "${concurrent_n} - ${concurrent_nom} : ${concurrent_montant} (${concurrent_statut}) - Motif: ${concurrent_motif}"

        # Make Table 6 the "gagnant"
        table = doc.tables[6]
        if len(table.rows) > 0 and len(table.rows[0].cells) > 0:
            table.rows[0].cells[0].text = "${attributaire_nom}"

    doc.save(out_path)

prepare_template(r'storage\app\templates\resultats_ao.docx', r'storage\app\templates\resultats_ao.docx')
prepare_template(r'storage\app\templates\rapport_presentation.docx', r'storage\app\templates\rapport_presentation.docx')

print("Templates modified.")
