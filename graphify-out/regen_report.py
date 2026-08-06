"""Regenerate GRAPH_REPORT.md with community labels."""
import json
from pathlib import Path
from graphify.build import build_from_json
from graphify.cluster import score_all
from graphify.analyze import god_nodes, surprising_connections, suggest_questions
from graphify.report import generate

GRAPHSON = Path("graphify-out/graph.json")
LABELS   = Path("graphify-out/.graphify_labels.json")
ANALYSIS = Path("graphify-out/.graphify_analysis.json")
DETECT   = Path("graphify-out/.graphify_detect.json")

raw = json.loads(GRAPHSON.read_text(encoding="utf-8"))
G = build_from_json(raw, root=Path.cwd(), directed=False)

if ANALYSIS.exists():
    analysis = json.loads(ANALYSIS.read_text(encoding="utf-8"))
    communities = {int(k): v for k, v in analysis.get("communities", {}).items()}
    cohesion = {int(k): v for k, v in analysis.get("cohesion", {}).items()}
else:
    print("No analysis file - please re-cluster")
    raise SystemExit(1)

labels: dict[int, str] = {}
if LABELS.exists():
    raw_lbl = json.loads(LABELS.read_text(encoding="utf-8"))
    labels = {int(k): v for k, v in raw_lbl.items()}

detection = {}
if DETECT.exists():
    try: detection = json.loads(DETECT.read_text(encoding="utf-8"))
    except: pass

gods = god_nodes(G)
surprises = surprising_connections(G, communities)
questions = suggest_questions(G, communities, labels)
tokens = {"input": 0, "output": 0}

report = generate(G, communities, cohesion, labels, gods, surprises, detection, tokens, str(Path.cwd()), suggested_questions=questions)
Path("graphify-out/GRAPH_REPORT.md").write_text(report, encoding="utf-8")
print("Report regenerated.")

# Print the key sections
print("\n=== GOD NODES ===")
for g in gods[:15]:
    print(f"  {g['label']} (degree={g['degree']}, betweenness={g.get('betweenness','?')})")

print(f"\n=== SURPRISING CONNECTIONS ({len(surprises)} total) ===")
for s in surprises[:10]:
    print(f"  {s.get('node1','')} <-> {s.get('node2','')}")

print(f"\n=== SUGGESTED QUESTIONS ({len(questions)} total) ===")
for q in questions[:10]:
    print(f"  {q}")