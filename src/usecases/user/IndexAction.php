<?php

declare(strict_types=1);

namespace app\usecases\user;

use app\usecases\shared\view\ViewRendererInterface;
use Yii;
use yii\filters\{AccessControl, VerbFilter};
use yii\web\{Action, Response};

/**
 * Lists users through {@see UserSearch}, exposing the resulting {@see ActiveDataProvider} and search model to the
 * frontend overlay's renderer.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class IndexAction extends Action
{
    /**
     * Creates a new instance with the renderer supplied by the active frontend overlay.
     *
     * @param ViewRendererInterface $view Renderer used to produce the user list view.
     */
    public function __construct(private readonly ViewRendererInterface $view)
    {
        parent::__construct();
    }

    /**
     * Restricts the action to the admin role and to GET requests.
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [['allow' => true, 'roles' => ['admin']]],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['*' => ['GET']],
            ],
        ];
    }

    /**
     * Renders the paginated, filterable user list.
     *
     * @return Response|string Rendered user list view, or an HTTP response when the overlay sends headers directly.
     */
    public function run(): Response|string
    {
        $searchModel = new UserSearch();

        /** @var array<string, mixed> $queryParams */
        $queryParams = Yii::$app->request->queryParams;

        $dataProvider = $searchModel->search($queryParams);

        return $this->view->render(
            'user/index',
            [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
            ],
        );
    }
}
