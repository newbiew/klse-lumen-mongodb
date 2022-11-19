<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

use App\Models\AnnouncementPage;
use App\Models\Quarterly_report;


class GeneratePage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:page';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to generate announcements page and break into QR tables.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // $annId = 3306470; // none qr

        $annId = AnnouncementPage::max("ann_id");

        if (!$annId) {
            $annId = 3307646;
        } else {
            $annId++;
        }

        $checkExist = AnnouncementPage::where("ann_id", $annId)->first();
        $domDoc = new \DOMDocument();
        if (!$checkExist) {
            $response = Http::get("https://disclosure.bursamalaysia.com/FileAccess/viewHtml?e=" . $annId . "#https://www.bursamalaysia.com/market_information/announcements/company_announcement/announcement_details?ann_id=" . $annId);


            // $response = Http::get('https://disclosure.bursamalaysia.com/FileAccess/viewHtml?e=3287212#https://www.bursamalaysia.com/market_information/announcements/company_announcement/announcement_details?ann_id=3287212');

            $html =  $response->getBody();

            @$domDoc->loadHTML($html);

            $domDoc->preserveWhiteSpace = true;
            if (strpos($domDoc->getElementsByTagName('body')->item(0)->nodeValue, "not found")) {
                return;
            } else {
                $this->saveHtml($annId, $domDoc);
                $checkType = $domDoc->getElementsByTagName('h3');
                if (strpos($checkType->item(0)->nodeValue, 'Quarterly')) { //if quarterly report is available
                    return $this->storeQuarterlyReport($domDoc);
                }
            }
        }

        //qr announcements
        // $response = Http::get('https://disclosure.bursamalaysia.com//FileAccess//viewHtml?e=3305102#https:\/\/www.bursamalaysia.com\/market_information\/announcements\/company_announcement\/announcement_details?ann_id=3305102');

        //other announcements



    }




    private function saveHtml($annId, $domDoc) //save html page to mongodb
    {

        // save to database
        $html = new AnnouncementPage;
        $html->ann_id = $annId;
        $html->content = $domDoc->saveHTML();
        $html->save();
    }

    private function storeQuarterlyReport($domDoc)
    {
        $data = $this->quarterlyReport($domDoc);

        $store = Quarterly_report::create([
            'company_name' => trim($data[10]),
            'short_name' => $data[11],
            'announcement_date' => $data[12],
            'category' => $data[13],
            'reference_number' => $data[14],
            'financial_year_end' => $data[0],
            'qr_number' => $data[1],
            'current_period_end' => $data[2],
            'the_figures' => $data[3],
            'revenue' => ($data[4] * 1000),
            'pl_before_tax' => ($data[5] * 1000),
            'pl_after_tax' => $data[6] * 1000,
            'current_pl' => $data[7] * 1000,
            'current_preceding_year_percentage' => $data[8],
            'current_earning_per_share' => $data[9],
        ]);
        return $store;
    }

    private function quarterlyReport($domDoc)
    {
        $tables = $domDoc->getElementsByTagName('table');

        $qrRows = $tables->item(0)->getElementsByTagName('tbody');

        $financialRows = $tables->item(0)->getElementsByTagName('tbody');
        $informationRow = $tables->item(2)->getElementsByTagName('tr');

        for ($j = 0; $j < 10; $j++) {
            if (!strpos($financialRows->item(0)->nodeValue, "Revenue")) {
                $financialRows = $tables->item(0 + $j)->getElementsByTagName('tbody');
            } else {
                break;
            }
        }

        for ($j = 0; $j < 10; $j++) {
            if (!strpos($informationRow->item(0)->nodeValue, "Company Name")) {
                $informationRow = $tables->item(2 + $j)->getElementsByTagName('tr');
            } else {
                break;
            }
        }

        for ($k = 0; $k < 10; $k++) {
            if (!strpos($qrRows->item(0)->nodeValue, "Financial")) {
                $qrRows = $tables->item(0 + $k)->getElementsByTagName('tbody');
            } else {
                break;
            }
        }

        $rawData = [];

        $tr = $qrRows->item(0)->getElementsByTagName('tr');

        $column1 = $tr->item(0)->getElementsByTagName('td');
        $rawData[0] = str_replace("\n", "", $column1->item(1)->nodeValue); // Financial year end date

        $column2 = $tr->item(1)->getElementsByTagName('td');
        $rawData[1] = str_replace("\n", "", $column2->item(1)->nodeValue); // number of current QR

        $column3 = $tr->item(2)->getElementsByTagName('td');
        $rawData[2] = str_replace("\n", "", $column3->item(1)->nodeValue); // QR current financial period ended

        $column4 = $tr->item(3)->getElementsByTagName('td');
        $rawData[3] = str_replace("\n", "", $column4->item(1)->nodeValue); // the figures (optional)

        $tr = $financialRows->item(0)->getElementsByTagName('tr');


        $column5s =  $tr->item(5)->getElementsByTagName('td');
        $column5 = str_replace("\n", "", $column5s->item(1)->nodeValue);


        if (strpos($column5, "Revenue") > -1) {
            $rawData[4] = $this->floatvalue(str_replace("\n", "", $column5s->item(2)->nodeValue)); // revenue
        }


        $column6s =  $tr->item(6)->getElementsByTagName('td');
        $column6 = str_replace("\n", "", $column6s->item(1)->nodeValue);
        if (strpos($column6, "before tax") > -1) {
            $rawData[5] = $this->floatvalue(str_replace("\n", "", $column6s->item(2)->nodeValue)); //P/L before tax
        }

        $column7s =  $tr->item(7)->getElementsByTagName('td');
        $column7 = str_replace("\n", "", $column7s->item(1)->nodeValue);

        if (strpos($column7, "the period") > -1) {
            $rawData[6] = $this->floatvalue(str_replace("\n", "", $column6s->item(2)->nodeValue)); //P/L after tax
        }


        $column8s =  $tr->item(8)->getElementsByTagName('td');
        $column8 = str_replace("\n", "", $column8s->item(1)->nodeValue);
        if (strpos($column8, "attributable") > -1) {

            $currentpl = $this->floatvalue(str_replace("\n", "", $column8s->item(2)->nodeValue));
            $precedingpl = $this->floatvalue(str_replace("\n", "", $column8s->item(3)->nodeValue));

            $rawData[7] = $this->floatvalue(str_replace("\n", "", $column8s->item(2)->nodeValue)); //current pl amount
            $rawData[8] = ($currentpl - $precedingpl) / $precedingpl * 100; //current preceding percentage
        }

        $column9s =  $tr->item(9)->getElementsByTagName('td');
        $column9 = str_replace("\n", "", $column9s->item(1)->nodeValue);
        if (strpos($column9, "earnings") > -1) {
            $rawData[9] = $this->floatvalue(str_replace("\n", "", $column9s->item(2)->nodeValue));
        }

        // $column10s =  $tr->item(10)->getElementsByTagName('td');
        // $column10 = str_replace("\n", "", $column10s->item(1)->nodeValue);
        // if (strpos($column10, "dividend") > -1) {
        //     // $rawData[10] = $this->floatvalue(str_replace("\n", "", $column10s->item(2)->nodeValue)); //dividend
        // }

        $column11s =  $tr->item(11)->getElementsByTagName('td');
        $column11 = str_replace("\n", "", $column11s->item(1)->nodeValue);
        //if (strpos($column10, "dividend")) {
        // $rawData[11] = $column11;
        //$this->floatvalue(str_replace("\n", "", $column11s->item(2)->nodeValue));
        //}

        // $rawData[10] = $column5 . " - " . $column6 . " - " . $column7 . " - " . $column8;



        //     $i++;
        // }

        $col1 = $informationRow->item(0)->getElementsByTagName('td');
        $rawData[10] = $col1->item(1)->nodeValue; //company name

        $col2 = $informationRow->item(1)->getElementsByTagName('td');
        $rawData[11] = $col2->item(1)->nodeValue; //company code name

        $col3 = $informationRow->item(2)->getElementsByTagName('td');
        $rawData[12] = $col3->item(1)->nodeValue; //announcements date

        $col4 = $informationRow->item(3)->getElementsByTagName('td');
        $rawData[13] = $col4->item(1)->nodeValue; //category

        $col5 = $informationRow->item(4)->getElementsByTagName('td');
        $rawData[14] = $col5->item(1)->nodeValue; //reference number

        return $rawData;
    }

    private function floatvalue($val)
    {
        $val = str_replace(",", "", $val);
        $val = preg_replace('/\.(?=.*\.)/', '', $val);
        return floatval($val);
    }
}
