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
                    let index = dataKey.substring(3);
                    index = index.substring(0, 1).toLowerCase() + index.substring(1);
                    config[index] = dataset[dataKey] as string;
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