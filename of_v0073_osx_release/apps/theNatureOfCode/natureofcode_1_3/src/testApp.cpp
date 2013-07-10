#include "testApp.h"

int padding = 100;

//--------------------------------------------------------------
void testApp::setup(){
    location.set(100, 100, -500);
    velocity.set(2.5, 5);
}

//--------------------------------------------------------------
void testApp::update(){
    location += velocity;
    if ((location.x > ofGetWidth()-padding) || (location.x < padding)) {
        velocity.x = velocity.x * -1;
    }
    if ((location.y > ofGetHeight()-padding) || (location.y < padding)) {
        velocity.y = velocity.y * -1;
    }
    if ((location.z > 3*padding) || (location.z < padding)) {
        velocity.z = velocity.z * -1;
    }
}

//--------------------------------------------------------------
void testApp::draw(){
    ofBackground(60, 60, 60);
    ofSphere(location.x, location.y, location.z, 20);
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