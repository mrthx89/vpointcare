"""Load existing graph.json and export Obsidian vault + HTML."""
import json, sys
from pathlib import Path
from graphify.build import build_from_json
from graphify.cluster import cluster, score_all
from graphify.analyze import god_nodes, surprising_connections, suggest_questions
from graphify.report import generate
from graphify.export import to_json, to_obsidian, generate_html

# Paths
GRAPHSON = Path("graphify-out/graph.json")
LABELS   = Path("graphify-out/.graphify_labels.json")
OBSIDIAN = Path("graphify-out/obsidian")  # fallback: inside writable root
OUT_HTML = Path("graphify-out/graph.html")
DETECT   = Path("graphify-out/.graphify_detect.json")

# 1. Load graph
raw = json.loads(GRAPHSON.read_text(encoding="utf-8"))
print(f"Graph loaded: {len(raw.get('nodes',[]))} nodes, {len(raw.get('links',raw.get('edges',[])))} links")

# 2. Build NetworkX graph from existing graph.json
#    (the build_from_json function accepts the graph.json format too -
#     it internally handles nodes/links format)
G = build_from_json(raw, root=Path.cwd(), directed=False)
print(f"NetworkX graph: {G.number_of_nodes()} nodes, {G.number_of_edges()} edges")

# 3. Load or compute communities
#    Check if analysis file exists with communities
ANALYSIS = Path("graphify-out/.graphify_analysis.json")
if ANALYSIS.exists():
    analysis = json.loads(ANALYSIS.read_text(encoding="utf-8"))
    communities = {int(k): v for k, v in analysis.get("communities", {}).items()}
    cohesion = {int(k): v for k, v in analysis.get("cohesion", {}).items()}
    print(f"Loaded {len(communities)} communities from analysis")
else:
    print("Re-clustering...")
    communities = cluster(G)
    cohesion = score_all(G, communities)
    print(f"Clustered into {len(communities)} communities")

# 4. Load labels
labels: dict[int, str] = {}
if LABELS.exists():
    raw_lbl = json.loads(LABELS.read_text(encoding="utf-8"))
    labels = {int(k): v for k, v in raw_lbl.items()}
    print(f"Loaded {len(labels)} community labels")

# 5. Generate Obsidian vault
print(f"\nGenerating Obsidian vault -> {OBSIDIAN}")
node_count = to_obsidian(G, communities, str(OBSIDIAN), labels, cohesion)
print(f"Obsidian: wrote {node_count} files to {OBSIDIAN}")

# 6. Generate HTML
print(f"\nGenerating HTML -> {OUT_HTML}")
member_counts = {cid: len(mems) for cid, mems in communities.items()}
generate_html(G, communities, str(OUT_HTML), labels, member_counts)
print(f"HTML: written to {OUT_HTML}")

# 7. Regenerate GRAPH_REPORT.md with labels
print(f"\nRegenerating GRAPH_REPORT.md...")
gods = god_nodes(G)
surprises = surprising_connections(G, communities)
questions = suggest_questions(G, communities, labels)
tokens = {"input": 0, "output": 0}
detection = {}
if DETECT.exists():
    try:
        detection = json.loads(DETECT.read_text(encoding="utf-8"))
    except: pass
report = generate(G, communities, cohesion, labels, gods, surprises, detection, tokens, str(Path.cwd()), suggested_questions=questions)
Path("graphify-out/GRAPH_REPORT.md").write_text(report, encoding="utf-8")
print("Report updated.")

# Summary
print(f"\nDone! graphify-out/")
print(f"  obsidian/    - {node_count} note files")
print(f"  graph.html   - interactive graph")
print(f"  GRAPH_REPORT.md - audit report")
print(f"  graph.json   - raw data")
print(f"\nCommunities: {len(communities)}  |  Nodes: {G.number_of_nodes()}  |  Edges: {G.number_of_edges()}")