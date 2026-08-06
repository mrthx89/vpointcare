import graphify
print("graphify path:", graphify.__file__)
import importlib, pkgutil
# Try to find export modules
for importer, modname, ispkg in pkgutil.iter_modules(graphify.__path__):
    print(f"  module: {modname}" + (" (pkg)" if ispkg else ""))