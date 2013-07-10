#include "testApp.h"

int rows = 50;
int cols = 50;
int sidelength = 100;
float factor;
float noisy = 0.0;
int timer = 0;
float z[50][50];


//--------------------------------------------------------------
void testApp::setup(){
    ofSetVerticalSync(true);
    ofEnableSmoothing();
    glEnable(GL_DEPTH_TEST);
    ofEnableNormalizedTexCoords();
    
    ///setup camera
    cam.setPosition(ofVec3f(700,700,700));
    
    ///setup light
    ofEnableLighting();
    GLfloat light_ambient[] = { 0.0, 0.2, 0.0, 1.0 };
    GLfloat light_diffuse[] = { 0.0, 1.0, 0.0, 1.0 };
    GLfloat light_specular[] = { 0.0, 1.0, 0.0, 1.0 };
    GLfloat light_position[] = { 1.0, 1.0, 1.0, 0.0 };
    
    glLightfv(GL_LIGHT0, GL_AMBIENT, light_ambient);
    glLightfv(GL_LIGHT0, GL_DIFFUSE, light_diffuse);
    glLightfv(GL_LIGHT0, GL_SPECULAR, light_specular);
    glLightfv(GL_LIGHT0, GL_POSITION, light_position);
    
    glEnable(GL_LIGHT0);
    
    GLfloat light_ambient1[] = { 0.0, 0.0, 0.2, 1.0 };
    GLfloat light_diffuse1[] = { 0.0, 0.0, 1.0, 1.0 };
    GLfloat light_specular1[] = { 0.0, 0.0, 1.0, 1.0 };
    GLfloat light_position1[] = { -1.0, 1.0, 1.0, 0.0 };
    
    glLightfv(GL_LIGHT1, GL_AMBIENT, light_ambient1);
    glLightfv(GL_LIGHT1, GL_DIFFUSE, light_diffuse1);
    glLightfv(GL_LIGHT1, GL_SPECULAR, light_specular1);
    glLightfv(GL_LIGHT1, GL_POSITION, light_position1);
    
    glEnable(GL_LIGHT1);
    
    //shaders
    myshader.load("myShader");
    
    ofSetFrameRate(60);
    ofBackground(0);
    
}

//--------------------------------------------------------------
void testApp::update(){
    for(int a=0; a<=rows; a++) {
        for(int b=0; b<=cols; b++) {
            noisy = ofNoise(a*b*factor);
            noisy = ofMap(ofNoise(sin(noisy)), 0, 1, 0, sidelength*3);
            z[a][b] = noisy;
        }
    }
    
    factor += 0.0001;
    timer++;
}

//--------------------------------------------------------------
void testApp::draw(){

    cam.begin();
    //rotate light around origin ofviewspace
    float xx=0;
    float yy=sin(ofGetElapsedTimef()*0.4)*150;
    float zz=cos(ofGetElapsedTimef()*0.4)*150;
    
    GLfloat light_position[] = { xx, yy, zz, 0.0 };
    GLfloat light_position1[] = { xx, yy, -zz, 0.0 };
    glLightfv(GL_LIGHT0, GL_POSITION, light_position);
    glLightfv(GL_LIGHT1, GL_POSITION, light_position1);
    
    ofPushStyle();
    ofRotateY(30);
    ofSetColor(200,100,100);
    ofBox(0,0,0,200);
    ofPopStyle();
    
    ofBackground(50, 50, 50);
    
    ofRotateX(60);
    ofTranslate(0, 0, 0);
    
    ofPushMatrix();
        // center the grid on the screen
    ofTranslate(-((cols*sidelength)/2), -((rows*sidelength)/2), 0);
    
        myshader.begin();
    
    
        // build the grid
        ofColor(255, 100, 100);
        ofFill();
        for(int i=0; i < rows; i++) {
            for(int j=0; j < cols; j++) {
                ofBeginShape();
                    ofVertex(j*sidelength, i*sidelength, -z[i][j]);
                    ofVertex(j*sidelength+sidelength, i*sidelength, -z[i][j+1]);
                    ofVertex(j*sidelength+sidelength, i*sidelength+sidelength, -z[i+1][j+1]);
                    ofVertex(j*sidelength, i*sidelength+sidelength, -z[i+1][j]);
                ofEndShape();
            }
        }
    
        myshader.end();
    
    ofPopMatrix();
    
    cam.end();
}

//--------------------------------------------------------------
void testApp::keyPressed(int key){

}

//--------------------------------------------------------------
void testApp::keyReleased(int key){

}

//--------------------------------------------------------------
void testApp::mouseMoved(int x, int y ){

}

//--------------------------------------------------------------
void testApp::mouseDragged(int x, int y, int button){

}

//--------------------------------------------------------------
void testApp::mousePressed(int x, int y, int button){

}

//--------------------------------------------------------------
void testApp::mouseReleased(int x, int y, int button){

}

//--------------------------------------------------------------
void testApp::windowResized(int w, int h){

}

//--------------------------------------------------------------
void testApp::gotMessage(ofMessage msg){

}

//--------------------------------------------------------------
void testApp::dragEvent(ofDragInfo dragInfo){ 

}