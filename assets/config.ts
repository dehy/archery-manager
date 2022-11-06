class Config {
    config: { [key: string]: string } = {};

    constructor() {
        this.config = this.loadConfig();
    }

    loadConfig = () => {
        const dataset = document.querySelector('body')?.dataset;
        const config: { [key: string]: string } = {};
        if (dataset) {
            for (const dataKey in dataset) {
                if (dataKey.startsWith('env')) {
                    config[dataKey] = dataset[dataKey] as string;
                }
            }
        }

        return config;
    }

    dump = () => {
        return this.config;
    }

    get = (key: string) => {
        return this.config[key];
    }
}

const config = new Config();

export default config;