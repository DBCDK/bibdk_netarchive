#!/usr/bin/env python
# -*- encoding: utf-8 -*-

import simplejson as json

# Following patterns from the gfy.py example in reportlab
import sys,os

from reportlab.platypus import *
from reportlab.lib.styles import getSampleStyleSheet
from reportlab.rl_config import defaultPageSize
from reportlab.lib.enums import TA_CENTER

PAGE_HEIGHT=defaultPageSize[1]
styles=getSampleStyleSheet()

from reportlab.lib.units import mm

# Build page
def build_front_page(canvas,doc):
    canvas.saveState()
    main_footer.wrapOn(canvas,160*mm,30*mm)
    main_footer.drawOn(canvas, 25*mm, 35*mm)
    canvas.setSubject('Kopi fra DBC Webarkiv')
    canvas.setTitle('Unimplemented title function')
    canvas.restoreState()

def build_body_section(title,label,data):
    ret=[]
    ret.append(Spacer(60*mm,25*mm))
    title_style=styles['Heading1']
    title_style.alignment=TA_CENTER
    ret.append(Paragraph(title,styles['Heading1']))
    ret.append(Paragraph(label,styles['Heading2']))
    ret.append(Paragraph(data,styles['Normal']))
    return ret

# Get input and output-file as argument
if len(sys.argv) < 2:
    print >>sys.stderr, "ERROR: No filename supplied."
    os.abort()

infile=sys.argv[1]
outfile_temp=infile.replace('.json','_temp.pdf')
outfile=infile.replace('.json','.pdf')

# Sanity check
assert(os.access(infile, os.R_OK))

indata_raw=open(infile,'r').readlines()
indata_json=indata_raw[0]

sections=[]
body_data=json.loads(indata_json,encoding='iso-8859-1')

sections+=(build_body_section(body_data['overskrift'],
                              body_data["kopi_af"],
                              body_data["data"]))

main_footer=Paragraph(body_data['footer'], styles["Normal"])

# Build frontpage
if os.access(outfile_temp,os.R_OK):
    print >>sys.stderr, "ERROR: Temporary file (%s) exists already. Bailing"%outfile_temp
    os.abort()

doc = SimpleDocTemplate(outfile_temp)
doc.build(sections,onFirstPage=build_front_page)

# Call Ghostscipt to merge files
pdffile=body_data['pdffile']
assert(os.access(pdffile,os.R_OK))
assert(os.access(outfile_temp,os.R_OK))

# GS args for conversion
run_args=['-dSAFER',
          '-q',
          '-sDEVICE=pdfwrite',
          '-sPAPERSIZE=a4',
          '-dBATCH',
          '-dNOPAUSE',
          '-sOutputFile=%s'%outfile,
          outfile_temp,
          pdffile]

if os.access(outfile,os.R_OK):
    print >>sys.stderr, "ERROR: Outputfile (%s) exists already. Bailing"%outfile
    if os.access(outfile_temp,os.R_OK):
        os.remove(outfile_temp)
    os.abort()

gs_ret=os.spawnvp(os.P_WAIT,'gs',run_args)
assert(os.access(outfile,os.R_OK))
os.remove(outfile_temp)

sys.exit(gs_ret)
