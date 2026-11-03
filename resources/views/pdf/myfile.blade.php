<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Recaps View PDF</title>
  <style>
    body {
      font-family: system-ui, sans-serif;
      margin: 0;
      padding: 0;
      background-color: white;
      line-height: 1.4;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 15px;
    }

    .column {
      width: 100%;
      padding: 15px;
      box-sizing: border-box;
    }

    .section-title {
      font-size: 14px;
      text-transform: uppercase;
      font-weight: 600;
      color: #212529;
      margin-top: 15px;
      margin-bottom: 5px;
      padding-bottom: 3px;
    }

    .info-box, .demo-checklist {
      font-size: 14px;
      color: black;
      padding: 10px;
      border: 1px solid #e5e5e5;
      background-color: #f9f9f9;
      margin-bottom: 15px;
    }

    .product-table {
      width: 100%;
      border-collapse: collapse;
      border: 1px solid #e5e5e5;
      margin-bottom: 15px;
    }

    .product-table th, .product-table td {
      padding: 7px;
      border-bottom: 1px solid #e5e5e5;
      text-align: left;
      font-size: 13px;
    }

    .product-table th {
      background-color: #f5f5f5;
    }

    .detail-label {
      font-weight: 500;
      font-size: 14px;
      margin-bottom: 3px;
      text-transform: uppercase;
    }

    .detail-value {
      font-weight: 400;
      font-size: 14px;
      margin-bottom: 10px;
    }

    .page-title {
      font-size: 20px;
      font-weight: 600;
      margin-bottom: 5px;
      text-transform: uppercase;
    }

    .page-subtitle {
      font-size: 14px;
      font-weight: 500;
      margin-bottom: 15px;
    }

    .demo-checklist ol {
      margin: 0;
      padding-left: 25px;
    }

    .event-photos {
      padding: 15px;
      border-top: 1px solid #e5e5e5;
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    .photo img {
      width: 180px;
      height: 180px;
      object-fit: cover;
      border: 1px solid #e5e5e5;
    }

    /* PRINT STYLES FOR PDF OUTPUT */
    @media print {
      body {
        margin: 0;
        padding: 0;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        background: white;
        font-family: system-ui, sans-serif;
      }

      @page {
        size: A4;
        margin: 1cm;
      }

      .container {
        max-width: 100%;
        margin: 0;
        padding: 0;
      }
      .column {
        width: 100%;
        padding: 10px;
        box-sizing: border-box;
        page-break-inside: avoid;
      }

      .event-photos {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 20px;
        border-top: 1px solid #e5e5e5;
        page-break-before: always;
      }

      .photo img {
        page-break-inside: avoid;
        break-inside: avoid;
      }

      table, .info-box, .demo-checklist {
        page-break-inside: avoid;
      }
    }
  </style>
</head>
<body>
  <div class="container">

      <div class="column">
        <div class="page-title">OFF-PREMISE EVENT: WOODMAN'S - SUN PRAIRIE</div>
        <div class="page-subtitle">Brand: Nosotros Tequila</div>

        <div class="section-title">DESCRIBE TRAFFIC FLOW</div>
        <div class="info-box">Very busy then slow on repeat. Obviously a lot of Holiday shoppers trying to look for boozy gifts.</div>

        <div class="section-title">DEMOGRAPHICS</div>
        <div class="info-box"><strong>FEMALE 60%</strong><br><strong>MALE 40%</strong></div>

        <div class="section-title">AGE RANGE</div>
        <div class="info-box"><strong>50+</strong></div>

        <div class="section-title">SAMPLING NUMBER</div>
        <div class="info-box"><strong>14</strong></div>

        <div class="section-title">AMOUNT OF PRODUCT SOLD</div>
        <table class="product-table">
          <thead>
            <tr>
              <th>Type</th>
              <th>Name</th>
              <th>Sold</th>
              <th>Regular Price</th>
              <th>Sale Price</th>
            </tr>
          </thead>
          <tbody>
            <tr><td>SPIRIT</td><td>Nosotros Blanco</td><td>2</td><td>$31.99</td><td>$31.99</td></tr>
            <tr><td>SPIRIT</td><td>Nosotros Reposado</td><td>3</td><td>$35.99</td><td>$35.99</td></tr>
            <tr><td>SPIRIT</td><td>Nosotros Blanco</td><td>2</td><td>$31.99</td><td>$31.99</td></tr>
            <tr><td>SPIRIT</td><td>Nosotros Reposado</td><td>3</td><td>$35.99</td><td>$35.99</td></tr>
          </tbody>
        </table>

        <div class="section-title">BUY BACK</div>
        <div class="info-box"><strong>$73.21</strong><br>$73.21 : Nosotros Tequila & Mezcal</div>

        <div class="demo-checklist">
          <ol>
            <li>Was the store aware of the demo? Yes</li>
            <li>Was the store prepared for the demo? Yes</li>
            <li>Was the product on sale? No</li>
            <li>Were there coupons or rebates available? No</li>
            <li>Was there enough product in stock? Yes</li>
            <li>Was there a case display? No</li>
            <li># of Cases in Display:</li>
          </ol>
        </div>

        <div class="section-title">OBJECTIONS TO SALE</div>
        <div class="info-box">Lots of No's on tequila. No tequila drinking on a Sunday. Tequila doesn't sit well with them. Would have preferred a lime or salt with the demo sample.</div>

        <div class="section-title">CONSUMER COMMENTS</div>
        <div class="info-box">The Blanco was super smooth with a subtle flowery flavor. Reposado not too dry or overly strong and the price was very reasonable.</div>

        <div class="section-title">ACCOUNT FEEDBACK (OPTIONAL)</div>
        <div class="info-box">I've done a few of these demos and this seemed to have the most traction out of the last couple of Nosotros demos I've done.</div>
      </div>

      <div class="column">
        <div class="section-title">OTHER DETAILS</div>

         <table class="product-table">
          <tbody>
            <tr>
              <th style="width: 115px; vertical-align: top;"><p class="detail-label" style="margin: 0;">LOCATION</p></th>
              <td>
                <div class="detail-value">Woodman's - Sun Prairie</div>
                <div class="detail-value">31</div>
              </td>
            </tr>
            <tr>
              <th style="width: 115px; vertical-align: top;"><p class="detail-label" style="margin: 0;">START</p></th>
              <td>
                <div class="detail-value">12/15/24 2:00 PM</div>
              </td>
            </tr>
            <tr>
              <th style="width: 115px; vertical-align: top;"><p class="detail-label" style="margin: 0;">END</p></th>
              <td>
                <div class="detail-value">12/15/24 4:00 PM</div>
              </td>
            </tr>
            <tr>
              <th style="width: 115px; vertical-align: top;"><p class="detail-label" style="margin: 0;">DISTRIBUTOR</p></th>
              <td>
                <div class="detail-value">Frank Liquor</div>
              </td>
            </tr>

            <tr>
              <th style="width: 115px; vertical-align: top;"><p class="detail-label" style="margin: 0;">PAYMENT TYPE</p></th>
              <td>
                <div class="detail-value">Gift Card</div>
              </td>
            </tr>
           
          </tbody>
        </table>

        <div class="detail-value"><strong>Selling</strong></div>
      </div>
   
    <div class="event-photos">
      <div class="photo"><img src="https://picsum.photos/200/300" alt="Event Photo 1" /></div>
      <div class="photo"><img src="https://picsum.photos/200/300" alt="Event Photo 2" /></div>
      <div class="photo"><img src="https://picsum.photos/200/300" alt="Event Photo 3" /></div>
      <div class="photo"><img src="https://picsum.photos/200/300" alt="Event Photo 4" /></div>
    </div>
  </div>
</body>
</html>
