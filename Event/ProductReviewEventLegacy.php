<?php
/**
 * This file is part of the ProductReview plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\ProductReview\Event;

use Eccube\Entity\Master\Disp;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class ProductReviewEventLegacy.
 *
 * @deprecated since 3.0.0 - 3.0.8
 */
class ProductReviewEventLegacy extends CommonEvent
{
    /**
     * フロント：商品詳細画面に商品レビューを表示します.
     * @param FilterResponseEvent $event
     */
    public function onRenderProductsDetailBefore(FilterResponseEvent $event)
    {
        // カート内でも呼ばれるためGETに限定
        if ($event->getRequest()->getMethod() === 'GET') {
            $app = $this->app;

            $limit = $app['config']['review_regist_max'];
            $id = $app['request']->attributes->get('id');
            $Product = $app['eccube.repository.product']->find($id);
            $Disp = $app['eccube.repository.master.disp']
                ->find(Disp::DISPLAY_SHOW);
            $ProductReviews = $app['product_review.repository.product_review']
                ->findBy(array(
                    'Product' => $Product,
                    'Status' => $Disp
                ),
                array('create_date' => 'DESC'),
                $limit === null ? 5 : $limit
            );

            $twig = $app->renderView(
                'ProductReview/Resource/template/default/product_review.twig',
                array(
                    'id' => $id,
                    'ProductReviews' => $ProductReviews,
                )
            );

            $response = $event->getResponse();

            $html = $response->getContent();
            $crawler = new Crawler($html);

            $oldElement = $crawler
                ->filter('#item_detail_area .item_detail');

            $oldHtml = $oldElement->html();
            $oldHtml = html_entity_decode($oldHtml, ENT_NOQUOTES, 'UTF-8');
            $newHtml = $oldHtml.$twig;

            $html = $this->getHtml($crawler);
            $html = str_replace($oldHtml, $newHtml, $html);

            $response->setContent($html);
            $event->setResponse($response);
        }
    }
}
