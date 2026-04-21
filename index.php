<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalkulator Waktu Sholat & Durasi Puasa</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="bg-white p-6 md:p-8 rounded-3xl shadow-2xl w-full max-w-2xl border border-slate-200">
        <h1 class="text-2xl font-black text-slate-800 mb-6 text-center tracking-tight">Kalkulator Waktu Sholat Pro</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
                <section>
                    <h3 class="text-xs font-black text-blue-600 uppercase mb-3 flex items-center">
                        <span class="bg-blue-600 w-1 h-3 mr-2 rounded-full"></span> Lokasi & Waktu
                    </h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Pilih Kota</label>
                            <select id="citySelect" onchange="setCity()" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none font-semibold text-sm">
                                <option value="-7.25,112.75,7">Surabaya (WIB)</option>
                                <option value="-6.20,106.84,7">Jakarta (WIB)</option>
                                <option value="3.59,98.67,7">Medan (WIB)</option>
                                <option value="-5.14,119.41,8">Makassar (WITA)</option>
                                <option value="kustom">-- Input Manual --</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" id="lat" placeholder="Lat" value="-7.25" class="px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-500 font-mono">
                            <input type="text" id="lng" placeholder="Lng" value="112.75" class="px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-500 font-mono">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="date" id="dateInput" class="px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-sm outline-none">
                            <div class="relative">
                                <input type="number" id="tz" value="7" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-sm outline-none">
                                <span class="absolute right-3 top-2 text-[10px] text-slate-400 font-bold italic">TZ</span>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="space-y-4">
                <section>
                    <h3 class="text-xs font-black text-emerald-600 uppercase mb-3 flex items-center">
                        <span class="bg-emerald-600 w-1 h-3 mr-2 rounded-full"></span> Kriteria Perhitungan
                    </h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Metode Shubuh/Isya</label>
                            <select id="methodSelect" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-sm outline-none">
                                <option value="20,18">Singapore / Kemenag (20°, 18°)</option>
                                <option value="18,17">MWL (18°, 17°)</option>
                                <option value="15,15">ISNA (15°, 15°)</option>
                                <option value="18.5,90">Makkah (18.5°, 90m)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Metode Ashar</label>
                            <select id="asharMethod" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-sm outline-none">
                                <option value="1">Syafi'i, Maliki, Hanbali (t=1)</option>
                                <option value="2">Hanafi (t=2)</option>
                            </select>
                        </div>
                        <button onclick="updateSchedule()" class="w-full bg-slate-800 hover:bg-black text-white font-bold py-3 rounded-xl transition-all shadow-lg active:scale-95 text-sm uppercase tracking-widest">
                            Hitung Jadwal
                        </button>
                    </div>
                </section>
            </div>
        </div>

        <div id="results" class="mt-8 grid grid-cols-2 sm:grid-cols-3 gap-3 bg-slate-50 p-4 rounded-3xl border border-slate-200 shadow-inner">
            </div>

        <div id="durationStats" class="mt-6 p-5 bg-white border border-slate-100 rounded-2xl space-y-2">
            </div>
    </div>

    <script>
        document.getElementById('dateInput').valueAsDate = new Date();

        function setCity() {
            const val = document.getElementById('citySelect').value;
            if(val === "kustom") return;
            const [lat, lng, tz] = val.split(',');
            document.getElementById('lat').value = lat;
            document.getElementById('lng').value = lng;
            document.getElementById('tz').value = tz;
            updateSchedule();
        }

        const calc = {
            degToRad: (deg) => (deg * Math.PI) / 180,
            radToDeg: (rad) => (rad * 180) / Math.PI,
            fixHour: (h) => ((h % 24) + 24) % 24,
            fixAngle: (a) => ((a % 360) + 360) % 360,

            getJulianDate: (date) => {
                let y = date.getFullYear(), m = date.getMonth() + 1, d = date.getDate();
                if (m <= 2) { y -= 1; m += 12; }
                let A = Math.floor(y / 100);
                let B = 2 - A + Math.floor(A / 4);
                return Math.floor(365.25 * (y + 4716)) + Math.floor(30.6001 * (m + 1)) + d + B - 1524.5;
            },

            calculate: (lat, lng, tz, jd, config) => {
                const d = jd - 2451545.0;
                let g = calc.fixAngle(357.529 + 0.98560028 * d);
                let q = calc.fixAngle(280.459 + 0.98564736 * d);
                const L = calc.fixAngle(q + 1.915 * Math.sin(calc.degToRad(g)) + 0.020 * Math.sin(calc.degToRad(2 * g)));
                const e = 23.439 - 0.00000036 * d;
                const RA = calc.radToDeg(Math.atan2(Math.cos(calc.degToRad(e)) * Math.sin(calc.degToRad(L)), Math.cos(calc.degToRad(L)))) / 15;
                const D = calc.radToDeg(Math.asin(Math.sin(calc.degToRad(e)) * Math.sin(calc.degToRad(L))));
                const EqT = q / 15 - RA;

                let dhuhr = 12 + tz - (lng / 15) - EqT;

                const getT = (alpha) => {
                    const cosL = Math.cos(calc.degToRad(lat));
                    const cosD = Math.cos(calc.degToRad(D));
                    const sinL = Math.sin(calc.degToRad(lat));
                    const sinD = Math.sin(calc.degToRad(D));
                    const sinAlpha = Math.sin(calc.degToRad(alpha));
                    const val = (-sinAlpha - (sinL * sinD)) / (cosL * cosD);
                    if (Math.abs(val) > 1) return null;
                    return (1 / 15) * calc.radToDeg(Math.acos(val));
                };

                const getAshar = (t) => {
                    const term = t + Math.abs(Math.tan(calc.degToRad(lat - D)));
                    const acot = calc.radToDeg(Math.atan(1 / term));
                    const val = (Math.sin(calc.degToRad(acot)) - Math.sin(calc.degToRad(lat)) * Math.sin(calc.degToRad(D))) / 
                                (Math.cos(calc.degToRad(lat)) * Math.cos(calc.degToRad(D)));
                    if (Math.abs(val) > 1) return null;
                    return (1 / 15) * calc.radToDeg(Math.acos(val));
                };

                const maghribT = getT(0.833);
                const sunriseT = getT(0.833);
                const shubuhT = getT(config.sShubuh);
                const ishaVal = config.sIsya === 90 ? (maghribT !== null ? maghribT + 1.5 : null) : getT(config.sIsya);

                const times = {
                    shubuh: shubuhT !== null ? calc.fixHour(dhuhr - shubuhT) : null,
                    sunrise: sunriseT !== null ? calc.fixHour(dhuhr - sunriseT) : null,
                    dhuhr: calc.fixHour(dhuhr + (2/60)),
                    ashar: getAshar(config.tAshar) !== null ? calc.fixHour(dhuhr + getAshar(config.tAshar)) : null,
                    maghrib: maghribT !== null ? calc.fixHour(dhuhr + maghribT) : null,
                    isya: ishaVal !== null ? calc.fixHour(dhuhr + ishaVal) : null,
                    rawMaghrib: maghribT !== null ? dhuhr + maghribT : null,
                    rawSunrise: sunriseT !== null ? dhuhr - sunriseT : null,
                    rawShubuh: shubuhT !== null ? dhuhr - shubuhT : null
                };

                return times;
            },

            format: (dec) => {
                if (dec === null || isNaN(dec)) return "--:--";
                const h = Math.floor(dec);
                const m = Math.round((dec - h) * 60);
                if (m === 60) return `${(h + 1).toString().padStart(2, '0')}:00`;
                return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
            },

            formatDuration: (hoursDec) => {
                if (hoursDec === null || isNaN(hoursDec)) return "Tidak dapat dihitung";
                const h = Math.floor(hoursDec);
                const m = Math.round((hoursDec - h) * 60);
                return `<strong>${h}</strong> jam <strong>${m}</strong> menit`;
            }
        };

        function updateSchedule() {
            const lat = parseFloat(document.getElementById('lat').value.replace(',', '.'));
            const lng = parseFloat(document.getElementById('lng').value.replace(',', '.'));
            const tz = parseFloat(document.getElementById('tz').value);
            const [sShubuh, sIsya] = document.getElementById('methodSelect').value.split(',').map(Number);
            const tAshar = parseInt(document.getElementById('asharMethod').value);
            const date = new Date(document.getElementById('dateInput').value);

            const jd = calc.getJulianDate(date);
            const times = calc.calculate(lat, lng, tz, jd, {sShubuh, sIsya, tAshar});
            
            // Render Jadwal
            const resultsBox = document.getElementById('results');
            resultsBox.innerHTML = '';
            const list = [
                {n: "Shubuh", v: times.shubuh}, {n: "Terbit", v: times.sunrise},
                {n: "Dzuhur", v: times.dhuhr}, {n: "Ashar", v: times.ashar},
                {n: "Maghrib", v: times.maghrib}, {n: "Isya", v: times.isya}
            ];
            list.forEach(i => {
                const s = calc.format(i.v);
                resultsBox.innerHTML += `
                    <div class="bg-white p-4 rounded-2xl flex flex-col items-center shadow-sm border border-slate-100">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">${i.n}</span>
                        <span class="text-xl font-black ${s==='--:--'?'text-slate-300':'text-slate-800'} tracking-tighter">${s}</span>
                    </div>`;
            });

            // Hitung Durasi
            const siangJam = (times.rawMaghrib && times.rawSunrise) ? (times.rawMaghrib - times.rawSunrise) : null;
            const puasaJam = (times.rawMaghrib && times.rawShubuh) ? (times.rawMaghrib - times.rawShubuh) : null;

            document.getElementById('durationStats').innerHTML = `
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-500">Lama waktu siang hari:</span>
                    <span class="text-slate-800">${calc.formatDuration(siangJam)}</span>
                </div>
                <div class="flex justify-between items-center text-sm pt-2 border-t border-slate-50">
                    <span class="text-emerald-600 font-bold">Lama waktu puasa:</span>
                    <span class="text-emerald-700">${calc.formatDuration(puasaJam)}</span>
                </div>
            `;
        }
        updateSchedule();
    </script>
</body>
</html>