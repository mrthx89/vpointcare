import json
from pathlib import Path

g = json.loads(Path('graphify-out/graph.json').read_text(encoding='utf-8'))
nodes = g.get('nodes', [])
edges = g.get('edges', [])
print(f"EXISTING graph.json: {len(nodes)} nodes, {len(edges)} edges")
print(f"Keys: {list(g.keys())}")
print(f"Directed: {g.get('directed', 'N/A')}")
print(f"Communities: {len(g.get('communities', []))} or {g.get('community_count')}")

try:
    ast = json.loads(Path('graphify-out/.graphify_ast.json').read_text(encoding='utf-8'))
    print(f"NEW AST: {len(ast.get('nodes', []))} nodes, {len(ast.get('edges', []))} edges")
except Exception as e: 
    print(f"NEW AST: error - {e}")

try:
    lbl = json.loads(Path('graphify-out/.graphify_labels.json').read_text(encoding='utf-8'))
    print(f"Labels: {len(lbl)} communities")
    for k,v in list(lbl.items())[:5]: print(f"  {k}: {v}")
except Exception as e: print(f"Labels: not found - {e}")