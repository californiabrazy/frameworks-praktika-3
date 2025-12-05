<?php
namespace App\Http\Controllers;
use App\Http\Repositories\CmsPageRepository;

class CmsController extends Controller {
  protected CmsPageRepository $cmsPageRepository;

  public function __construct(CmsPageRepository $cmsPageRepository) {
    $this->cmsPageRepository = $cmsPageRepository;
  }

  public function page(string $slug) {
    $row = $this->cmsPageRepository->getPageBySlug($slug);
    if (!$row) abort(404);
    return response()->view('cms.page', ['title' => $row->title, 'html' => $row->body]);
  }
}
