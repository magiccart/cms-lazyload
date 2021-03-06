<?php

namespace MageSuite\CmsLazyload\Plugin\Cms\Block\Block;

class InjectDataSrcTagIntoImages
{
    const LAZYLOAD_CSS_CLASS = 'lazyload';
    const SRCSET_PLACEHOLDER = 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';

    /**
     * @var \MageSuite\CmsLazyload\Helper\Configuration
     */
    protected $configuration;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(
        \MageSuite\CmsLazyload\Helper\Configuration $configuration,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->configuration = $configuration;
        $this->logger = $logger;
    }

    public function afterToHtml(\Magento\Cms\Block\Block $subject, $result)
    {
        if (!$this->configuration->isEnabled()) {
            return $result;
        }

        if (empty($result)) {
            return $result;
        }

        try {
            $dom = new \DomDocument();
            $dom->loadHTML(mb_convert_encoding('<html>' . $result . '</html>','HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        } catch (\Exception $e) {
            $this->logger->warning('Issue when adjusting CMS block images to be lazy loaded. Provided HTML code is incorrect, base64 of HTML: ' . base64_encode($result));

            return $result;
        }

        $dom->formatOutput = false;

        $images = $dom->getElementsByTagName('img');

        /** @var \DOMElement $image */
        foreach ($images as $image) {
            $src = $image->getAttribute('src');

            if (empty($src)) {
                continue;
            }

            $dataSrc = $image->getAttribute('data-src');

            if (!empty($dataSrc)) {
                continue;
            }

            $dataSrcSet = $image->getAttribute('data-srcset');

            if (!empty($dataSrcSet)) {
                continue;
            }

            $image->setAttribute('data-srcset', $src);
            $image->setAttribute('srcset', self::SRCSET_PLACEHOLDER);
            $image->setAttribute('class', $image->getAttribute('class') . ' ' . self::LAZYLOAD_CSS_CLASS);
        }

        $newHtml = $dom->saveHTML();
        $newHtml = str_replace(['<html>', '</html>'], '', $newHtml);
        $newHtml = rtrim($newHtml, PHP_EOL);

        return $newHtml;
    }
}
