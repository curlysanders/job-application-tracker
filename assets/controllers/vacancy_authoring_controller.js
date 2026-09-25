import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['minimumSalary', 'maximumSalary', 'currency', 'workMode', 'hybridDetailsContainer', 'salaryGuidance'];
    static values = { minimumPreferredSalary: String, minimumPreferredSalaryCurrency: String, rates: Object };

    connect() {
        this.update();
    }

    update() {
        this.hybridDetailsContainerTarget.hidden = this.workModeTarget.value !== 'HYBRID';
        const maximum = Number(this.maximumSalaryTarget.value);
        const minimum = Number(this.minimumSalaryTarget.value);
        const target = Number(this.minimumPreferredSalaryValue);
        const rate = this.conversionRate(this.currencyTarget.value, this.minimumPreferredSalaryCurrencyValue);
        const comparable = this.minimumPreferredSalaryValue !== '' && rate !== null && Number.isFinite(maximum) && maximum > 0 && Number.isFinite(target) && target > 0;
        if (!comparable) {
            this.salaryGuidanceTarget.hidden = true;
            return;
        }
        this.salaryGuidanceTarget.hidden = false;
        const convertedMaximum = maximum * rate;
        const convertedMinimum = minimum * rate;
        if (convertedMaximum < target) {
            this.salaryGuidanceTarget.className = 'salary-guidance salary-guidance-warning';
            this.salaryGuidanceTarget.textContent = `Below your ${target.toFixed(2)} ${this.minimumPreferredSalaryCurrencyValue} monthly target.`;
            return;
        }
        if (Number.isFinite(minimum) && minimum > 0 && convertedMinimum < target) {
            this.salaryGuidanceTarget.className = 'salary-guidance salary-guidance-neutral';
            this.salaryGuidanceTarget.textContent = `This range crosses your ${target.toFixed(2)} ${this.minimumPreferredSalaryCurrencyValue} monthly target.`;
            return;
        }
        this.salaryGuidanceTarget.hidden = true;
    }

    conversionRate(sourceCurrency, targetCurrency) {
        if (sourceCurrency === targetCurrency) return 1;
        const sourceRate = sourceCurrency === 'EUR' ? 1 : Number(this.ratesValue[sourceCurrency]);
        const targetRate = targetCurrency === 'EUR' ? 1 : Number(this.ratesValue[targetCurrency]);
        return Number.isFinite(sourceRate) && sourceRate > 0 && Number.isFinite(targetRate) && targetRate > 0 ? targetRate / sourceRate : null;
    }
}
