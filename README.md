# URN Resolver open source server and `/.well-known/urn/` conventions
Open souce configurable software optimized to host your own [URN](https://en.wikipedia.org/wiki/Uniform_Resource_Name) conversor to resolvable URLs.


> @see https://github.com/EticaAI/HXL-Data-Science-file-formats/issues/13

----

**Table of contents**


<!-- TOC depthfrom:2 -->

- [The URN Resolver server](#the-urn-resolver-server)
    - [How to run local node](#how-to-run-local-node)
    - [How to run a production servers](#how-to-run-a-production-servers)
        - [Your first high-availability cluster deployment](#your-first-high-availability-cluster-deployment)
        - [Your first production single node recommended](#your-first-production-single-node-recommended)
- [The URN Resolver conventions/specifications](#the-urn-resolver-conventionsspecifications)
    - [/.well-known/urn/ convention](#well-knownurn-convention)
    - [urn.example.org: subdomain convention](#urnexampleorg-subdomain-convention)
        - [Existing examples](#existing-examples)
    - [Peer-to-peer URN Resolver Server conventions](#peer-to-peer-urn-resolver-server-conventions)
        - [URN Resolver Rules sharing](#urn-resolver-rules-sharing)
- [Test cases](#test-cases)
    - [URN:DOI](#urndoi)
    - [URN:GEO](#urngeo)
    - [URN:IETF](#urnietf)
    - [URN:ISSN](#urnissn)
    - [URN:LEX:BR](#urnlexbr)
    - [/.well-known/urn/](#well-knownurn)
- [License](#license)

<!-- /TOC -->

----

## The URN Resolver server

### How to run local node

Be sure to have something such as php 8.1. But PHP 7.4 also know to work

```bash
# Get a copy
git clone https://github.com/EticaAI/urn-resolver.git
cd urn-resolver/public

# Run PHP build-in server
php -S localhost:8000

# Visit home page: http://localhost:8000/
```

### How to run a production server(s)

#### Your first high-availability cluster deployment

On this example, `server-a.urn.example.org`, `server-b.urn.example.org` and `server-b.urn.example.org` are assumed to be direct access to any type of web hosting able to run PHP (inclusive cheap shared hosting) which also respond for `urn.example.org`.
This example allows [High-availability cluster](https://en.wikipedia.org/wiki/High-availability_cluster) with [https://en.wikipedia.org/wiki/Round-robin_DNS](https://en.wikipedia.org/wiki/Round-robin_DNS) as load balancing strategy: just make sure `urn.example.org` points to IPs of server-a, server-b and server-c.

```bash
# Get a recent copy from some place.
git clone https://github.com/EticaAI/urn-resolver.git

# Configure your node
cp urnresolver.dist.conf.json urnresolver.conf.json
vim urnresolver.conf.json

# Replace DRY_RUN="1" with DRY_RUN="0" (disable rsync --dry-run) and remote
DRY_RUN="1" RSYNC_REMOTE="user@server-a.urn.example.org/home/user/public_html" ./scripts/sync-node-a.sh
DRY_RUN="1" RSYNC_REMOTE="user@server-b.urn.example.org/home/user/public_html" ./scripts/sync-node-a.sh
DRY_RUN="1" RSYNC_REMOTE="user@server-c.urn.example.org/home/user/public_html" ./scripts/sync-node-a.sh

```

> **Warning**: if you run a cluster behind free solution which also does cache but hide the true IPs from end user (like free Cloudflare; which we do recommend) you will need to check manually if servers are online and [STONITH - ("Shoot The Offending Node In The Head")](https://en.wikipedia.org/wiki/STONITH) and remove nodes not working.

> **Note**
> While most reverse proxies (such as Cloudflare even without paid load balance plans) will try next node if the entire source server is offline (e.g. rebooting) at least user browsers such as Chrome will also automatically check the next IP if they receive an 5xx server error (even if the server is online, but your app is failing).
This _poor's man load balancing_ works, but is a last resort.
It cannot cope if 1/3 (1 out of 3 nodes) or 1/2 (50% of your nodes) are online and (worst) reply 200 OK, but content is "welcome to nginx" / "welcome to apache".

#### Your first production single node (recommended)

Use the same strategy for the high-availability cluster, but with a single node.

This strategy is simpler to keep online in particular if the number of requests is not able to be worth the trouble to keep load balancers.

## The URN Resolver conventions/specifications

> The key words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL
> NOT", "SHOULD", "SHOULD NOT", "RECOMMENDED",  "MAY", and
> "OPTIONAL" in this document are to be interpreted as described in
> [RFC 2119](https://www.rfc-editor.org/rfc/rfc2119).

### /.well-known/urn/ convention

> **Warning**: the entire `/.well-known/urn/` is a draft.
> The intent is to describe the strategy used on the software implementation in a way that is not vendor dependent.
> Feel free to check the actual content of the folder [public/.well-known/urn](public/.well-known/urn).

The files published on `/.well-known/urn/` are inspired by the [RFC 8615 - Well-Known Uniform Resource Identifiers (URIs)](https://www.rfc-editor.org/rfc/rfc8615).
Do exist a list of [IANA Well-Known URIs](https://www.iana.org/assignments/well-known-uris/well-known-uris.xhtml),
however at the moment (2022-09-25), no submission as made (even as provisional) to IANA.
In the meantime, the quick overview of the implementation, which is open for feedback,
to explain the files is:

- `/.well-known/urn/urn.txt` (<http://urn.example.org/.well-known/urn/urn.txt>)
  - It's an index file. This TXT file lists all files on the server under the `/.well-known/urn/`.
  - The file names **MUST** be a regex rule to give a hint of what rules the entire file is about without need to read file by file. In this document this is referred as `<FILENAME_RULE_GROUP>`
  - The filename stops at `.urnr` part of the file.
  - The container of the rules **MUST** start after the `.urnr`.
  - For maximum interoperability **MUST** have at least JSON format. However, this format **MUST NOT** be a [JSON Schema](https://json-schema.org/) and do not use same extension as JSON documents
    - Example: `.urnr.json` (can reference JSON Schema, but not a JSON schema itself)
  - Files under `/.well-known/urn/` **MAY** may have more than one encoding. However, files with same `<FILENAME_RULE_GROUP>` **MUST** be considered to be about the same resource even if some parser understands more than one format.
  - Example:
    - `urn:doi:(.*).urnr.json` (example of REQUIRED resource)
    - `urn:doi:(.*).urnr.yml` (example of alternative encoding in YAML)
    - `urn:doi:(.*).urnr.txt` (example of text file)
- `/.well-known/urn/<FILENAME_RULE_GROUP>.urnr.<FILE_CONTAINER_FORMAT>` (example: <http://urn.example.org/.well-known/urn/urn:doi:(.*).urnr.json>)
  - (...TODO explain more...)

### `urn.example.org`: subdomain convention

It's **RECOMMENDED** for new implementations to define a URN resolver with its own dedicated subdomain `urn.` both for signal intent and for performance reasons
(e.g. in case necessary move the resolver to different server infrastructure than content on some main site).

Example:

- `https://www.example.com` (or `https://example.com`):
  - `https://urn.example.com`
- `https://my-university.example.org`:
  - `https://urn.my-university.example.org`
- `https://my-department.my-university.example.org`:
  - `https://urn.my-department.my-university.example.org`

The URN Resolvers **MUST** be resolvable at the top level of the chosen domain.
This means it is forbidden to use subfolders
(even for testing environments)
as an entrypoint to avoid confusion with users about what is the URN content and what is the resolver.
The URN resolvers also **MUST NOT** require a query string or fragment string.

Example:

- User want know how to resolve this URN: `urn:example:123`
  - Conformant: `https://urn.my-university.example.org/urn:example:123`
  - NOT conformant (subfolder):
    - `https://urn.my-university.example.org/folder/urn:example:123`
  - NOT Conformant (query string):
    - `https://urn.my-university.example.org/?urn=urn:example:123`
  - NOT Conformant (fragment):
    - `https://urn.my-university.example.org/#urn:example:123`

There is no restriction for redirects after the initial request.
This means URN Resolvers, as long as public adversised entrypoint is conformant,
**MAY** make additional rewrites before redirect to external servers.
One common reason for this behavior are URNs which are more complex to process than shareable URN Resolver Rules to other public resolvers.
See Peer-to-peer section.

#### Existing examples

Know real world examples (not related to this convention) know to follow this logic of using `urn.` subdomain and no subfolder:

- <https://urn.fi/>
- <https://urn.issn.org/>

Counter examples (e.g, able to resolve own URNs on main domain):

- <https://www.doi.org/>


<!--

ssh://urn.etica.ai/home/urneticaai/urn.etica.ai/
-->

### Peer-to-peer URN Resolver Server conventions
<!--
One implication of how _/.well-known/urn/ conventions_ are designed to enable client-side URN conversions is it also allows server-to-server cooperation.
-->

An URN Resolver Server able to understand rules of its own `/.well-known/urn/` will also understand if rules are copied from another server to its own public folder.

#### URN Resolver Rules sharing

An operator from an URN Resolver Server **MAY** opt for use as reference to the rules from another server in an automated way.


the operators of URN Resolver servers may

Use case:

- `urn.op-geo.example.org` and `urn.op-generic.example.org` can resolve near same URNs
- User wants resolve the URN `urn:example:adm:ago?f=gpkg` and asks `urn.op-generic.example.org`
- (...)

<!-- 
  ## (...)

Check [resolvers](resolvers/) folder.

[![https://imgs.xkcd.com/comics/regular_expressions.png](https://imgs.xkcd.com/comics/regular_expressions.png)](https://xkcd.com/208/)

-->


## Test cases

### URN:DOI
- https://urn.etica.ai/urn:doi:10.1000/182

### URN:GEO
- https://urn.etica.ai/urn:geo:-19.9026,-44.0340;u=100000

### URN:IETF
- https://urn.etica.ai/urn:ietf:rfc:2141
- https://urn.etica.ai/urn:ietf:bcp:47

### URN:ISSN
- https://urn.etica.ai/urn:issn:1476-4687

### URN:LEX:BR
- https://urn.etica.ai/urn:lex:br:federal:lei:2008-06-19;11705
- https://urn.etica.ai/urn:resolver:about:urn:lex:br:federal:lei:2008-06-19;11705

### /.well-known/urn/
> @see https://en.wikipedia.org/wiki/Well-known_URI

<!--
## @TODO
### Load testing
- https://gist.github.com/denji/8333630
-->

## License

[![Public Domain](https://i.creativecommons.org/p/zero/1.0/88x31.png)](UNLICENSE)

To the extent possible under law, [Emerson Rocha](https://github.com/fititnt)
has waived all copyright and related or neighboring rights to this work to
[Public Domain](UNLICENSE).

Optionally, you can choose to use the [MIT License](https://opensource.org/licenses/MIT)
instead of Public Domain unlicense.