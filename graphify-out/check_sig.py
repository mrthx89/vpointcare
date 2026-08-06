import inspect
from graphify.export import to_obsidian, to_html, generate_html
print("to_obsidian:", inspect.signature(to_obsidian))
print("to_html:", inspect.signature(to_html))
print("generate_html:", inspect.signature(generate_html))