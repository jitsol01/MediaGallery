<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="2.0" 
                xmlns:html="http://www.w3.org/TR/REC-html40"
                xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
	<xsl:template match="/">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>XML Sitemap | Cool Pictures</title>
</head>
<body>

<h1>XML Site Map</h1>
<xsl:for-each select="sitemap:urlset/sitemap:url">
<xsl:variable name="itemURL">
<xsl:value-of select="sitemap:loc"/>
</xsl:variable>
<a href="{$itemURL}">
<xsl:value-of select="sitemap:loc"/>
</a><br />
</xsl:for-each>

<p />Copyright 2007, All rights reserved.

</body></html>
</xsl:template>
</xsl:stylesheet>
