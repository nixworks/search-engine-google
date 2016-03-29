<?php
/**
 * @license see LICENSE
 */

namespace Serps\SearchEngine\Google\Parser\Evaluated\Rule\Natural;

use Serps\Core\Serp\BaseResult;
use Serps\Core\Serp\IndexedResultSet;
use Serps\Core\UrlArchive;
use Serps\SearchEngine\Google\GoogleUrlArchive;
use Serps\SearchEngine\Google\Page\GoogleDom;
use Serps\SearchEngine\Google\Parser\ParsingRuleInterace;
use Serps\SearchEngine\Google\NaturalResultType;

class Map implements ParsingRuleInterace
{

    public function match(GoogleDom $dom, \DOMElement $node)
    {
        if ($dom->getXpath()->query("descendant::h3[@class='_Xhb']", $node)) {
            return self::RULE_MATCH_MATCHED;
        }
        return self::RULE_MATCH_NOMATCH;
    }

    public function parse(GoogleDom $dom, \DomElement $node, IndexedResultSet $resultSet)
    {

        $xPath = $dom->getXpath();

        $item = [
            'localPack' => function () use ($xPath, $node, $dom) {
                $localPackNodes = $xPath->query('descendant::div[@class="_oL"]/div', $node);
                $data = [];
                foreach ($localPackNodes as $localPack) {
                    $data[] = new BaseResult(NaturalResultType::MAP_PLACE, $this->parseItem($localPack, $dom));
                }
                return $data;
            },
            'mapUrl'    => function () use ($xPath, $node, $dom) {
                $mapATag = $xPath->query('descendant::div[@class="_wNi"]//a', $node)->item(0);
                if ($mapATag) {
                    return $dom->getUrl()->resolve($mapATag->getAttribute('href'));
                }
                return null;
            }

        ];

        $resultSet->addItem(new BaseResult(NaturalResultType::MAP, $item));
    }

    private function parseItem($localPack, GoogleDom $dom)
    {
        return [
            'title' => function () use ($localPack, $dom) {
                $item = $dom->getXpath()->query('descendant::div[@class="_pl"]/div[@class="_rl"]', $localPack)->item(0);
                if ($item) {
                    return $item->nodeValue;
                }
                return null;
            },
            'url' => function () use ($localPack, $dom) {
                $item = $dom->getXpath()->query('descendant::div[@class="_gt"]/a', $localPack)->item(1);
                if ($item) {
                    return UrlArchive::fromString($item->getAttribute('href'));
                }
                return null;
            },
            'street' => function () use ($localPack, $dom) {
                $item = $dom->getXpath()->query(
                    'descendant::div[@class="_pl"]/span[@class="rllt__details"]/div[3]/span',
                    $localPack
                )->item(0);
                if ($item) {
                    return $item->nodeValue;
                }
                return null;
            },

            'stars' => function () use ($localPack, $dom) {
                $item = $dom->getXpath()->query('descendant::span[@class="_PXi"]', $localPack)->item(0);
                if ($item) {
                    return $item->nodeValue;
                }
                return null;
            },

            'review' => function () use ($localPack, $dom) {
                $item = $dom->getXpath()->query(
                    'descendant::div[@class="_pl"]/span[@class="rllt__details"]/div[1]',
                    $localPack
                )->item(0);
                if ($item) {
                    if ($item->childNodes->length > 0 && !($item->childNodes->item(0) instanceof \DOMText)) {
                        return null;
                    } else {
                        return trim(explode('·', $item->nodeValue)[0]);
                    }
                }
                return null;
            },
        ];
    }
}