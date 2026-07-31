import json
from pathlib import Path
d = json.loads(Path("graphify-out/.graphify_detect.json").read_text(encoding="utf-8"))
print("SKIPPED SENSITIVE:")
for f in d.get("skipped_sensitive", []) or []:
    print("  ", f)
print()
import collections
ext = collections.Counter()
for cat in ("code","document","image"):
    for f in d["files"].get(cat, []):
        ext[cat + " " + Path(f).suffix] += 1
for k,v in ext.most_common(20):
    print(f"  {k}: {v}")
print()
print("scan_root:", d.get("scan_root"))
vendor = [f for cat in d["files"].values() for f in cat if "vendor" in f or "node_modules" in f]
print("vendor/node_modules files included:", len(vendor))
