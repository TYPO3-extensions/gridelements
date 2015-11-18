

.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. ==================================================
.. DEFINE SOME TEXTROLES
.. --------------------------------------------------
.. role::   underline
.. role::   typoscript(code)
.. role::   ts(typoscript)
   :class:  typoscript
.. role::   php(code)


FAQ
---

**Question: Are Grid Elements a full replacement for TemplaVoila?**

**Answer:** Grid Elements are a replacement for the backend related
features of TemplaVoila, since they will provide you with structured
views of containers and additional functionality like drag & drop,
references and handling of unused elements. But they will not provide
any kind of mapping tool to connect existing HTML elements to these
structures.

**Question: How can I connect my HTML templates to the structures?**

**Answer:** They can be connected just like the page templates by
using the TEMPLATE or FLUIDTEMPLATE objects of TypoScript. You will
even get prerendered virtual fields containing arrays or sets of
elements to put into the subparts and markers of a TEMPLATE or into
some variables of a FLUIDTEMPLATE.

**Question: Can I still use my TemplaVoila FCEs?**

**Answer:** Yes you can, since there is a flexform field, that Grid
Elements make use of, although it is not recommended for the creation
of new elements. But you will have to provide your own TypoScript
setups for the frontend output of these FCEs.

**Question: Why is it not recommended to use the flexform for the
creation of new content elements?**

**Answer:** The major problem will be to get the content out of these
flexform structures when you try to collect it while you are not on
the same page. Since there will be no normalised datastructure it can
be very hard to just create things like teaser menus or other kinds of
collections.

**Question: But how should I create new content elements with
individual input fields then?**

**Answer:** It's very easy to provide a new CType for the tt\_content
table with the TYPO3 API since this table already offers you lots of
different fields to make use of. Just create the TCA structure and add
a new content type via an extension or maybe via extTables.php,
provide some TypoScript for the frontend output and – voila – here you
go with a new content element.

**Question: Is it possible to create new elements based on grid
containers?**

**Answer:** Yes – but again this is currently not recommended, since
you would have to provide a fully fledged tt\_content record for each
and every part of this element. We are working on a concept with a
drastcially reduced table though, that can be connected using Grid
Containers. With this approach it will be possible to create new
elements based on basic content snippets like Headline, Image, Text,
Table and so on.

**Question: Why do you reload the page after D&D actions but the TYPO3
core does not?**

**Answer:** The reason for this decision is the behaviour of the TYPO3
core while working on page content. When an editor is working at some
content, others will be notified only, when they open the editing form
of that particular content element. While working with D&D within
a larger editorial team it happens quite often, that people change
content of the same page at the same time. Just imagine the mess that
could happen without reloading the current state of that page. So as
long as there is no locking of content available in TYPO3, we will
stick to this behaviour.


