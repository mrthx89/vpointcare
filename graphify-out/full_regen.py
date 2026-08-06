"""Full pipeline: build graph from graph.json, cluster, analyze, report."""
import json
from pathlib import Path
from graphify.build import build_from_json
from graphify.cluster import cluster, score_all
from graphify.analyze import god_nodes, surprising_connections, suggest_questions
from graphify.report import generate

GRAPHSON = Path("graphify-out/graph.json")
LABELS   = Path("graphify-out/.graphify_labels.json")
ANALYSIS = Path("graphify-out/.graphify_analysis.json")
DETECT   = Path("graphify-out/.graphify_detect.json")

raw = json.loads(GRAPHSON.read_text(encoding="utf-8"))
G = build_from_json(raw, root=Path.cwd(), directed=False)
print(f"Graph: {G.number_of_nodes()} nodes, {G.number_of_edges()} edges")

communities = cluster(G)
cohesion = score_all(G, communities)
print(f"Clustered: {len(communities)} communities")

labels: dict[int, str] = {}
if LABELS.exists():
    raw_lbl = json.loads(LABELS.read_text(encoding="utf-8"))
    labels = {int(k): v for k, v in raw_lbl.items()}

gods = god_nodes(G)
surprises = surprising_connections(G, communities)
questions = suggest_questions(G, communities, labels)
tokens = {"input": 0, "output": 0}

detection = {}
if DETECT.exists():
    try: detection = json.loads(DETECT.read_text(encoding="utf-8"))
    except: pass

report = generate(G, communities, cohesion, labels, gods, surprises, detection, tokens, str(Path.cwd()), suggested_questions=questions)
Path("graphify-out/GRAPH_REPORT.md").write_text(report, encoding="utf-8")

analysis = {
    "communities": {str(k): v for k, v in communities.items()},
    "cohesion": {str(k): v for k, v in cohesion.items()},
    "gods": gods,
    "surprises": surprises,
    "questions": questions,
}
Path("graphify-out/.graphify_analysis.json").write_text(json.dumps(analysis, indent=2, ensure_ascii=False), encoding="utf-8")
print("Analysis + report written.")

print("\n=== GOD NODES ===")
for g in gods[:12]:
    print(f"  {g.get('label')} (degree={g.get('degree')})")

print(f"\n=== SURPRISING CONNECTIONS ({len(surprises)} total) ===")
for s in surprises[:8]:
    print(f"  {s}")

print(f"\n=== SUGGESTED QUESTIONS ({len(questions)} total) ===")
for q in questions[:8]:
    print(f"  - {q}")