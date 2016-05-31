## Building blocks for JSKOS Wrappers

This repository contrains some helper classes and PHP traits to facilitate
writing JSKOS Wrappers:

### Checking identifier format

See **IDTrait**.

### Mapping RDF to JSKOS

To facilitate writing wrappers from RDF to JSKOS, a mapping can be written in
form of a YAML file (see `*Mapping.yml` files in directory `src/lib`). The Mapping
is implemented with class **JSKOS\RDFMapping**.

### Passing queries to a Lucene backend

See **LuceneTrait**.

