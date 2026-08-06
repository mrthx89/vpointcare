"""Generate community-summary HTML for WACS (6957 nodes > 5000 limit)."""
import json, os
from pathlib import Path
from graphify.build import build_from_json

GRAPHSON = Path("graphify-out/graph.json")
LABELS   = Path("graphify-out/.graphify_labels.json")
ANALYSIS = Path("graphify-out/.graphify_analysis.json")
OUT_HTML = Path("graphify-out/graph.html")

raw = json.loads(GRAPHSON.read_text(encoding="utf-8"))
G = build_from_json(raw, root=Path.cwd(), directed=False)

if ANALYSIS.exists():
    analysis = json.loads(ANALYSIS.read_text(encoding="utf-8"))
    communities = {int(k): v for k, v in analysis.get("communities", {}).items()}
else:
    from graphify.cluster import cluster
    communities = cluster(G)

labels: dict[int, str] = {}
if LABELS.exists():
    raw_lbl = json.loads(LABELS.read_text(encoding="utf-8"))
    labels = {int(k): v for k, v in raw_lbl.items()}

# Generate community overview HTML
communities_sorted = sorted(communities.items(), key=lambda x: len(x[1]), reverse=True)
html_lines = [
    "<!DOCTYPE html>",
    "<html><head><meta charset='utf-8'>",
    "<title>WACS Knowledge Graph</title>",
    "<style>body{font-family:system-ui,sans-serif;max-width:960px;margin:0 auto;padding:20px;background:#0d1117;color:#c9d1d9}",
    "h1{color:#58a6ff}a{color:#58a6ff}table{border-collapse:collapse;width:100%%}",
    "th,td{padding:6px 12px;text-align:left;border-bottom:1px solid #30363d}",
    "th{background:#161b22}.bar{display:inline-block;height:14px;background:#238636;border-radius:3px}",
    "</style></head><body>",
    f"<h1>WACS Knowledge Graph</h1>",
    f"<p>{G.number_of_nodes():,} nodes &#183; {G.number_of_edges():,} edges &#183; {len(communities)} communities</p>",
    f"<p>Full graph too large for interactive viz ({G.number_of_nodes():,} &gt; 5,000). Community summary below.</p>",
    "<hr>",
    "<table><tr><th>#</th><th>Label</th><th>Size</th><th>Distribution</th></tr>"
]
max_size = max(len(m) for m in communities.values()) if communities else 1
for i, (cid, members) in enumerate(communities_sorted, 1):
    lbl = labels.get(cid, f"Community {cid}")
    sz = len(members)
    pct = sz / max_size * 100
    bar = f'<span class="bar" style="width:{pct:.0f}px"></span>'
    html_lines.append(f"<tr><td>{cid}</td><td>{lbl}</td><td>{sz}</td><td>{bar} {sz}</td></tr>")
html_lines.extend(["</table><hr>",
    "<p><small>Open <a href='obsidian/'>Obsidian vault</a> for full exploration or use <code>graphify query</code></small></p>",
    "</body></html>"])

OUT_HTML.write_text("\n".join(html_lines), encoding="utf-8")
print(f"Community summary HTML written -> {OUT_HTML}")