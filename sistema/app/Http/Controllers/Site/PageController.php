<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\CaseStudy;
use App\Models\Faq;
use App\Models\Product;
use App\Models\Solution;

class PageController extends Controller
{
    public function home()
    {
        return view('site.home', [
            'solutions' => Solution::active()->get(),
            'cases' => CaseStudy::active()->get(),
        ]);
    }

    public function soluciones()
    {
        return view('site.soluciones', ['solutions' => Solution::active()->get()]);
    }

    public function tablero()
    {
        return view('site.tablero');
    }

    public function productos()
    {
        return view('site.productos', ['products' => Product::active()->get()]);
    }

    public function casos()
    {
        return view('site.casos', ['cases' => CaseStudy::active()->get()]);
    }

    public function nosotros()
    {
        return view('site.nosotros');
    }

    public function faq()
    {
        return view('site.faq', ['faqs' => Faq::active()->get()]);
    }
}
