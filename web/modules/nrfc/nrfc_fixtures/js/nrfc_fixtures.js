document.addEventListener("DOMContentLoaded", function () {
  const target = document.getElementById('nrfc-spreadsheet');
  // https://bossanova.uk/jspreadsheet/v4/
  // console.log("match_reports", drupalSettings.nrfc_fixtures.match_reports)
  const data = [];
  drupalSettings.nrfc_fixtures.rows.forEach((row) => {
    const fixture = [];
    fixture.push(false);
    fixture.push(row["nid"]);
    fixture.push(row["date_as_string"]);
    fixture.push(row["ko"]);
    fixture.push(row["home"]);
    fixture.push(row["match_type"]);
    fixture.push(row["opponent"]);
    fixture.push(row["result"]);
    fixture.push(row["report_as_string"]);
    fixture.push(row["referee"]);
    fixture.push(row["food"]);
    fixture.push(row["food_notes"]);
    data.push(fixture);
  })
  // console.log(drupalSettings.nrfc_fixtures.rows);
  // console.log(data);
  jspreadsheet(target, {
    data: data,
    columns: [
      {
        type: 'checkbox',
        title: 'Delete',
        width: 90
      },
      {
        type: 'string',
        title: 'UUID',
        width: 90,
        readOnly: true
      },
      {
        // type: 'calendar', TODO - Why does this mangle tha date
        type: 'string',
        title: 'Date',
        options: {
          format:'DD/MM/YYYY',
        },
        width: 90
      },
      {
        type: 'text',
        title: 'KO Time',
        width: 90
      },
      {
        type: 'dropdown',
        title: 'H/A',
        width: 120,
        source: [
          "Home",
          "Away",
        ]
      },
      {
        type: 'dropdown',
        title: 'Match type',
        width: 120,
        source: [
          "League",
          "Friendly",
          "Festival",
          "Tournament",
          "National Cup",
          "County Cup",
          "Cup (Other)",
        ]
      },
      {
        type: 'text',
        title: 'Opponent',
        width: 90
      },
      {
        type: 'text',
        title: 'Result',
        width: 90
      },
      {
        type: 'dropdown',
        title: 'Report',
        // url: drupalSettings.path.baseUrl + "admin/config/system/nrfc/nrfc_fixtures/match_reports",
        source: drupalSettings.nrfc_fixtures.match_reports,
        autocomplete:true,
        width: 90
      },
      {
        type: 'text',
        title: 'Referee',
        width: 90
      },
      {
        type: 'numeric',
        title: 'Food',
        width: 90
      },
      {
        type: 'text',
        title: 'Food notes',
        width: 90
      },
    ]
  });
});

async function nrfc_update_fixtures($team) {
  const data = jQuery('#nrfc-spreadsheet').jspreadsheet("getData");
  console.log("pathPrefix", drupalSettings.path.currentPath)
  console.log("data", data);
  const payload = [];
  data.forEach((item) => {
    const row = {}
    row["delete"] = item[0];
    row["nid"] = item[1];
    row["date"] = item[2];
    row["ko"] = item[3];
    row["home"] = item[4];
    row["match_type"] = item[5];
    row["opponent"] = item[6];
    row["result"] = item[7];
    row["report"] = item[8];
    row["referee"] = item[9];
    row["food"] = item[10];
    row["food_notes"] = item[11];
    payload.push(row);
  })
  console.log("payload", payload);
  debugger;
  const rawResponse = await fetch(drupalSettings.path.pathPrefix, {
    method: 'PUT',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(payload)
  });
  location.reload();
}
