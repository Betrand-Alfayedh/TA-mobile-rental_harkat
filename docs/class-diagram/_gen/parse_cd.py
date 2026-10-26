import re, json, html
S = open('../Class-Diagram.svg', encoding='utf8').read()
els = re.findall(r'<(rect|line|text)\b([^>]*?)(?:/>|>([^<]*)</text>)', S)
boxes = []
cur = None
for tag, attr, txt in els:
    g = lambda k: (re.search(k + r'="([^"]*)"', attr) or [0, None])[1]
    if tag == 'rect' and g('fill') == 'url(#gk)':
        cur = dict(x=float(g('x')), y=float(g('y')), w=float(g('width')), h=float(g('height')), name=None, st=None, lines=[], mem=[])
        boxes.append(cur)
    elif cur:
        if tag == 'line':
            cur['lines'].append(float(g('y1')))
        elif tag == 'text':
            t = html.unescape(txt)
            if g('font-weight') == '700':
                cur['name'] = t
            elif t.startswith('«'):
                cur['st'] = t
            elif g('fill') == '#8a6d3b':
                cur['mem'].append([float(g('y')), t, None])
            elif cur['mem'] and cur['mem'][-1][2] is None and abs(float(g('y')) - cur['mem'][-1][0]) < 0.1:
                cur['mem'][-1][2] = t
out = []
for b in boxes:
    sep = b['lines'][1] if len(b['lines']) > 1 else None
    attrs = [m[2] for m in b['mem'] if sep is None or m[0] < sep]
    meth = [m[2] for m in b['mem'] if sep is not None and m[0] >= sep]
    out.append(dict(name=b['name'], st=b['st'], w=b['w'], h=b['h'], attrs=attrs, meth=meth))
    print(b['name'], b['st'], b['w'], b['h'], len(attrs), len(meth))
json.dump(out, open('boxes.json', 'w', encoding='utf8'), ensure_ascii=False, indent=1)
