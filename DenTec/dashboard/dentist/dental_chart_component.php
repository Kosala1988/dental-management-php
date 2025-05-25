<?php
// Global Dental Chart with Anatomical Teeth Visualization

class DentalChart {
    private $teethData = [
        // Upper Right (Quadrant 1)
        '18' => ['type' => 'molar3', 'name' => 'Third Molar'],
        '17' => ['type' => 'molar2', 'name' => 'Second Molar'],
        '16' => ['type' => 'molar1', 'name' => 'First Molar'],
        '15' => ['type' => 'premolar2', 'name' => 'Second Premolar'],
        '14' => ['type' => 'premolar1', 'name' => 'First Premolar'],
        '13' => ['type' => 'canine', 'name' => 'Canine'],
        '12' => ['type' => 'incisor2', 'name' => 'Lateral Incisor'],
        '11' => ['type' => 'incisor1', 'name' => 'Central Incisor'],
        
        // Upper Left (Quadrant 2)
        '21' => ['type' => 'incisor1', 'name' => 'Central Incisor'],
        '22' => ['type' => 'incisor2', 'name' => 'Lateral Incisor'],
        '23' => ['type' => 'canine', 'name' => 'Canine'],
        '24' => ['type' => 'premolar1', 'name' => 'First Premolar'],
        '25' => ['type' => 'premolar2', 'name' => 'Second Premolar'],
        '26' => ['type' => 'molar1', 'name' => 'First Molar'],
        '27' => ['type' => 'molar2', 'name' => 'Second Molar'],
        '28' => ['type' => 'molar3', 'name' => 'Third Molar'],
        
        // Lower Left (Quadrant 3)
        '38' => ['type' => 'molar3', 'name' => 'Third Molar'],
        '37' => ['type' => 'molar2', 'name' => 'Second Molar'],
        '36' => ['type' => 'molar1', 'name' => 'First Molar'],
        '35' => ['type' => 'premolar2', 'name' => 'Second Premolar'],
        '34' => ['type' => 'premolar1', 'name' => 'First Premolar'],
        '33' => ['type' => 'canine', 'name' => 'Canine'],
        '32' => ['type' => 'incisor2', 'name' => 'Lateral Incisor'],
        '31' => ['type' => 'incisor1', 'name' => 'Central Incisor'],
        
        // Lower Right (Quadrant 4)
        '41' => ['type' => 'incisor1', 'name' => 'Central Incisor'],
        '42' => ['type' => 'incisor2', 'name' => 'Lateral Incisor'],
        '43' => ['type' => 'canine', 'name' => 'Canine'],
        '44' => ['type' => 'premolar1', 'name' => 'First Premolar'],
        '45' => ['type' => 'premolar2', 'name' => 'Second Premolar'],
        '46' => ['type' => 'molar1', 'name' => 'First Molar'],
        '47' => ['type' => 'molar2', 'name' => 'Second Molar'],
        '48' => ['type' => 'molar3', 'name' => 'Third Molar']
    ];

    private function getToothSVG($type, $x, $y, $isUpper, $toothNumber) {
        $shapes = [
            'incisor1' => [
                'path' => $isUpper 
                    ? "M10,0 C15,2 18,10 18,20 C18,30 10,35 2,20 C2,10 5,0 10,0 Z"
                    : "M10,35 C15,33 18,25 18,15 C18,5 10,0 2,15 C2,25 5,35 10,35 Z",
                'width' => 20,
                'height' => 35
            ],
            'incisor2' => [
                'path' => $isUpper 
                    ? "M10,0 C14,2 17,12 17,22 C17,30 10,35 3,22 C3,12 6,0 10,0 Z"
                    : "M10,35 C14,33 17,23 17,13 C17,5 10,0 3,13 C3,23 6,35 10,35 Z",
                'width' => 20,
                'height' => 35
            ],
            'canine' => [
                'path' => $isUpper 
                    ? "M10,0 C15,0 20,10 20,25 C20,35 10,40 0,25 C0,10 5,0 10,0 Z"
                    : "M10,40 C15,40 20,30 20,15 C20,5 10,0 0,15 C0,30 5,40 10,40 Z",
                'width' => 20,
                'height' => 40
            ],
            'premolar1' => [
                'path' => "M5,0 C15,0 20,10 20,25 C20,35 15,40 5,40 C0,40 0,25 0,10 C0,5 0,0 5,0 Z",
                'width' => 20,
                'height' => 40
            ],
            'premolar2' => [
                'path' => "M3,0 C17,0 20,10 20,25 C20,35 15,40 5,40 C0,40 0,25 0,10 C0,5 0,0 3,0 Z",
                'width' => 20,
                'height' => 40
            ],
            'molar1' => [
                'path' => "M0,5 C0,0 10,0 20,5 C25,10 25,20 20,35 C15,40 5,40 0,35 C0,20 0,10 0,5 Z",
                'width' => 25,
                'height' => 40
            ],
            'molar2' => [
                'path' => "M0,5 C0,0 10,0 20,5 C25,10 25,25 20,35 C15,40 5,40 0,35 C0,25 0,15 0,5 Z",
                'width' => 25,
                'height' => 40
            ],
            'molar3' => [
                'path' => "M0,5 C0,0 12,0 25,5 C30,10 30,25 25,35 C20,40 5,40 0,35 C0,25 0,15 0,5 Z",
                'width' => 25,
                'height' => 40
            ]
        ];

        $tooth = $shapes[$type] ?? $shapes['molar1'];
        $fill = '#ffffff';
        $stroke = '#333333';
        
        return sprintf(
            '<g transform="translate(%d,%d)" class="tooth" data-number="%s">
                <path d="%s" fill="%s" stroke="%s" stroke-width="1.5"/>
                <text x="%d" y="%d" text-anchor="middle" font-size="12" fill="#333">%s</text>
            </g>',
            $x, $y,
            $toothNumber,
            $tooth['path'],
            $fill, $stroke,
            $tooth['width']/2, $isUpper ? $tooth['height'] + 15 : -10,
            $toothNumber
        );
    }

    public function render() {
        $output = '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Dental Chart</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .chart-container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 900px; margin: 0 auto; }
                .dental-chart { margin: 20px 0; text-align: center; }
                .tooth path { transition: all 0.3s; cursor: pointer; }
                .tooth path:hover { fill: #e6f7ff; stroke-width: 2; }
                .tooth.selected path { fill: #b3d9ff !important; stroke: #0066cc; }
                .chart-title { text-align: center; margin-bottom: 20px; color: #333; }
                .quadrant-label { font-weight: bold; text-anchor: middle; }
            </style>
        </head>
        <body>
            <div class="chart-container">
                <h2 class="chart-title">Dental Chart (FDI Numbering System)</h2>
                <div class="dental-chart">
                    <svg width="800" height="500" viewBox="0 0 800 500">';
        
        // Upper Jaw
        $output .= '<g transform="translate(100,100)">';
        $output .= '<text x="400" y="-20" class="quadrant-label">Upper Jaw</text>';
        
        // Upper Right (18-11)
        $xPos = 0;
        foreach (['18','17','16','15','14','13','12','11'] as $tooth) {
            $output .= $this->getToothSVG($this->teethData[$tooth]['type'], $xPos, 0, true, $tooth);
            $xPos += 40;
        }
        
        // Upper Left (21-28)
        foreach (['21','22','23','24','25','26','27','28'] as $tooth) {
            $output .= $this->getToothSVG($this->teethData[$tooth]['type'], $xPos, 0, true, $tooth);
            $xPos += 40;
        }
        
        $output .= '</g>';
        
        // Lower Jaw
        $output .= '<g transform="translate(100,300)">';
        $output .= '<text x="400" y="-20" class="quadrant-label">Lower Jaw</text>';
        
        // Lower Left (38-31)
        $xPos = 0;
        foreach (['38','37','36','35','34','33','32','31'] as $tooth) {
            $output .= $this->getToothSVG($this->teethData[$tooth]['type'], $xPos, 0, false, $tooth);
            $xPos += 40;
        }
        
        // Lower Right (41-48)
        foreach (['41','42','43','44','45','46','47','48'] as $tooth) {
            $output .= $this->getToothSVG($this->teethData[$tooth]['type'], $xPos, 0, false, $tooth);
            $xPos += 40;
        }
        
        $output .= '</g>';
        
        // Midline
        $output .= '<line x1="50" y1="250" x2="750" y2="250" stroke="#999" stroke-width="1" stroke-dasharray="5,5"/>';
        $output .= '<text x="400" y="270" font-family="Arial" font-size="14" fill="#666" text-anchor="middle">MIDLINE</text>';
        
        $output .= '</svg></div></div>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const teeth = document.querySelectorAll(".tooth");
                    teeth.forEach(tooth => {
                        tooth.addEventListener("click", function() {
                            this.classList.toggle("selected");
                        });
                    });
                });
            </script>
        </body>
        </html>';
        
        return $output;
    }
}

// Usage
$dentalChart = new DentalChart();
echo $dentalChart->render();
?>