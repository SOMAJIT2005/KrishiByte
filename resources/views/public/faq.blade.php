@extends('layouts.app')

@section('title', 'Methodology & FAQ — KrishiByte')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="bg-info-kpi rounded p-2 d-flex flex-column align-items-center" style="min-width: 2.5rem;">
                        <i class="bi bi-question-circle fs-4" style="color: var(--kb-info);"></i>
                    </div>
                    <div>
                        <h2 class="fw-bold mb-1" style="color: var(--kb-text);">Methodology, Rules & Frequently Asked Questions</h2>
                        <p class="text-muted mb-0">Transparent operating definitions, statistical metrics, and governance principles behind KrishiByte.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0" style="border: 1px solid var(--kb-border);">
            <div class="card-body p-0">
                <div class="accordion accordion-flush" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header border-0">
                            <button class="accordion-button fw-semibold d-flex align-items-center justify-content-between w-100" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne" style="padding: 1.25rem 1.5rem; background: transparent; border: none; color: var(--kb-text);">
                                <span><span class="badge bg-light text-dark border badge-role me-2" style="font-size: 0.625rem;">Q1</span>1. What is KrishiByte and what problem does it address?</span>
                                <i class="bi bi-dash-down-short fs-5" style="color: var(--kb-text-muted);"></i>
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                            <div class="accordion-body px-4 pb-4" style="border-top: 1px solid var(--kb-border); color: var(--kb-text);">
                                <p class="mb-3">In Bangladesh's agricultural markets, prices frequently double or triple between rural farm gates (e.g., Bogura, Dinajpur) and urban kitchen markets (e.g., Karwan Bazar, Mohammadpur Krishi Market). KrishiByte digitizes every custody handoff from the grower to the retailer, computing the price spread at each step so that consumers, market monitors, and farmers can see whether price increases are driven by transport costs or speculative hoarding.</p>
                                <div class="p-3 rounded bg-light border text-center small mb-0" style="border-color: var(--kb-border); background: #f8fafc;">
                                    <i class="bi bi-arrow-right-arrow-left fs-4 d-block mb-2" style="color: var(--kb-primary-light);"></i>
                                    <strong>The core problem:</strong> Without transparent custody tracking, it is nearly impossible to distinguish legitimate supply chain margins from artificial price manipulation.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header border-0">
                            <button class="accordion-button collapsed fw-semibold d-flex align-items-center justify-content-between w-100" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo" style="padding: 1.25rem 1.5rem; background: transparent; border: none; color: var(--kb-text);">
                                <span><span class="badge bg-light text-dark border badge-role me-2" style="font-size: 0.625rem;">Q2</span>2. How are Gross Spread, Markup %, and Margin % defined?</span>
                                <i class="bi bi-dash-down-short fs-5" style="color: var(--kb-text-muted);"></i>
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                            <div class="accordion-body px-4 pb-4" style="border-top: 1px solid var(--kb-border);">
                                <p class="mb-3">To maintain rigorous mathematical accuracy, KrishiByte strictly follows standard accountancy definitions:</p>
                                <div class="row g-3 mt-3">
                                    <div class="col-md-4">
                                        <div class="p-3 rounded border text-center h-100" style="background: #f8fafc; border-color: var(--kb-border); border-left: 3px solid var(--kb-primary-light);">
                                            <div class="text-muted small mb-1">Gross Price Spread</div>
                                            <code class="d-block fw-bold" style="color: var(--kb-primary-light); font-size: 1.1rem;">S - B</code>
                                            <small class="text-muted">BDT per unit. Raw price increment before operational costs.</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-3 rounded border text-center h-100" style="background: #f8fafc; border-color: var(--kb-border); border-left: 3px solid var(--kb-warning);">
                                            <div class="text-muted small mb-1">Markup on Cost (%)</div>
                                            <code class="d-block fw-bold" style="color: var(--kb-warning); font-size: 1.1rem;">((S-B)/B) × 100</code>
                                            <small class="text-muted">Percentage increase on purchase price.</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-3 rounded border text-center h-100" style="background: #f8fafc; border-color: var(--kb-border); border-left: 3px solid var(--kb-info);">
                                            <div class="text-muted small mb-1">Gross Margin (%)</div>
                                            <code class="d-block fw-bold" style="color: var(--kb-info); font-size: 1.1rem;">((S-B)/S) × 100</code>
                                            <small class="text-muted">Share of selling price that is gross spread.</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 p-3 rounded bg-light border small" style="border-color: var(--kb-border); color: var(--kb-text-muted);">
                                    <i class="bi bi-info-circle me-1"></i>
                                    <strong>When a batch is in custody but not yet sold,</strong> KrishiByte displays "Pending next sale price" rather than inventing a speculative margin.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header border-0">
                            <button class="accordion-button collapsed fw-semibold d-flex align-items-center justify-content-between w-100" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree" style="padding: 1.25rem 1.5rem; background: transparent; border: none; color: var(--kb-text);">
                                <span><span class="badge bg-light text-dark border badge-role me-2" style="font-size: 0.625rem;">Q3</span>3. How is the Indicative Reference Price computed?</span>
                                <i class="bi bi-dash-down-short fs-5" style="color: var(--kb-text-muted);"></i>
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                            <div class="accordion-body px-4 pb-4" style="border-top: 1px solid var(--kb-border);">
                                <p class="mb-3">KrishiByte aggregates verified observations by crop, district, and date. To prevent artificial price distortion from isolated rogue prices, the platform applies a tiered statistical methodology:</p>
                                <div class="row g-3 mt-3">
                                    <div class="col-md-4">
                                        <div class="p-3 rounded bg-success bg-opacity-10 border text-center h-100" style="border-color: var(--kb-success);">
                                            <div class="text-success fw-bold mb-1" style="font-size: 0.875rem;">≥ 5 Surveys</div>
                                            <div class="text-success fs-4 fw-bold mb-1" style="font-family: monospace;">20% Trimmed Mean</div>
                                            <small class="text-muted">Top/bottom outliers removed</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-3 rounded bg-info bg-opacity-10 border text-center h-100" style="border-color: var(--kb-info);">
                                            <div class="text-info fw-bold mb-1" style="font-size: 0.875rem;">3-4 Surveys</div>
                                            <div class="text-info fs-4 fw-bold mb-1" style="font-family: monospace;">Median</div>
                                            <small class="text-muted">Middle value, robust to extremes</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-3 rounded bg-warning bg-opacity-10 border text-center h-100" style="border-color: var(--kb-warning);">
                                            <div class="text-warning fw-bold mb-1" style="font-size: 0.875rem;">&lt;3 Surveys</div>
                                            <div class="text-warning fs-4 fw-bold mb-1" style="font-family: monospace;">Insufficient Data</div>
                                            <small class="text-muted">Untrimmed average + warning</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 p-3 rounded bg-light border small" style="border-color: var(--kb-border); color: var(--kb-text-muted);">
                                    <i class="bi bi-shield-check me-1" style="color: var(--kb-primary-light);"></i>
                                    <strong>Important:</strong> KrishiByte never fabricates prices or claims official government endorsement without legitimate data from the Department of Agricultural Marketing, Bangladesh Bureau of Statistics, or authorized field monitors.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header border-0">
                            <button class="accordion-button collapsed fw-semibold d-flex align-items-center justify-content-between w-100" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour" style="padding: 1.25rem 1.5rem; background: transparent; border: none; color: var(--kb-text);">
                                <span><span class="badge bg-light text-dark border badge-role me-2" style="font-size: 0.625rem;">Q4</span>4. What does an "Anomaly Flag" mean?</span>
                                <i class="bi bi-dash-down-short fs-5" style="color: var(--kb-text-muted);"></i>
                            </button>
                        </h2>
                        <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                            <div class="accordion-body px-4 pb-4" style="border-top: 1px solid var(--kb-border);">
                                <p class="mb-3">An anomaly flag is a statistical notice that a transaction's markup exceeded the configured fair-margin threshold for that crop and supply stage. These thresholds are configurable by administrators and are intended to reflect reasonable supply chain margins including transport, labor, spoilage, and market fees.</p>
                                <div class="alert alert-warning py-2 px-3 small mb-0" style="border-color: var(--kb-warning);">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                    <strong>An anomaly flag is an administrative trigger for human review, not proof of criminal conduct.</strong> Authorized market monitors examine legitimate explanations before clearing or escalating a case.
                                </div>
                                <ul class="small mt-3" style="color: var(--kb-text-muted);">
                                    <li class="mb-1">Emergency refrigeration or cold storage costs</li>
                                    <li class="mb-1">Severe freight road closures or transport disruption</li>
                                    <li class="mb-1">Extreme wastage from perishable spoilage</li>
                                    <li>Verified purchase from distressed farmer at market clearing price</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header border-0">
                            <button class="accordion-button collapsed fw-semibold d-flex align-items-center justify-content-between w-100" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive" style="padding: 1.25rem 1.5rem; background: transparent; border: none; color: var(--kb-text);">
                                <span><span class="badge bg-light text-dark border badge-role me-2" style="font-size: 0.625rem;">Q5</span>5. How are consumer reports moderated?</span>
                                <i class="bi bi-dash-down-short fs-5" style="color: var(--kb-text-muted);"></i>
                            </button>
                        </h2>
                        <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
                            <div class="accordion-body px-4 pb-4" style="border-top: 1px solid var(--kb-border);">
                                <p class="mb-3">Consumer reports enter a secure, private moderation queue accessible only to authorized authority users. Field officers verify the physical stall locations, cross-reference wholesale arrival prices at nearby arats, and interview the reporting consumer if additional evidence is needed.</p>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="p-3 rounded border small h-100" style="background: #f8fafc; border-color: var(--kb-border);">
                                            <div class="fw-semibold mb-2" style="color: var(--kb-text);">Verification Process:</div>
                                            <ul class="small mb-0" style="padding-left: 1.25rem; color: var(--kb-text-muted);">
                                                <li>Physical stall location verification</li>
                                                <li>Cross-reference wholesale arrival prices</li>
                                                <li>Consumer interview if needed</li>
                                                <li>Evidence photo examination</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 rounded border small h-100" style="background: #f8fafc; border-color: var(--kb-border);">
                                            <div class="fw-semibold mb-2" style="color: var(--kb-text);">Outcomes:</div>
                                            <ul class="small mb-0" style="padding-left: 1.25rem; color: var(--kb-text-muted);">
                                                <li><strong style="color: var(--kb-success);">Verified</strong> → Feeds public price benchmark</li>
                                                <li><strong style="color: var(--kb-danger);">Rejected</strong> → Unsubstantiated claim</li>
                                                <li><strong style="color: var(--kb-text-muted);">Duplicate</strong> → Already recorded</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
