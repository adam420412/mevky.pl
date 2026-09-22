# Run with fonttools[woff] installed. Retains Latin text, Polish letters and punctuation.
from pathlib import Path
from fontTools import subset
root=Path(__file__).resolve().parents[1]
for path in (root/'mevky/assets/fonts').glob('*.woff2'):
 options=subset.Options();options.flavor='woff2'
 font=subset.load_font(str(path),options)
 sub=subset.Subsetter(options=options)
 sub.populate(unicodes=list(range(0x20,0x100))+[ord(c) for c in 'ĄąĆćĘęŁłŃńÓóŚśŹźŻż']+list(range(0x2000,0x2070))+[0x20ac,0x2192,0x2212])
 sub.subset(font)
 target=root/'public/optimized'/path.name
 subset.save_font(font,str(target),options)
 print(path.name,target.stat().st_size)
